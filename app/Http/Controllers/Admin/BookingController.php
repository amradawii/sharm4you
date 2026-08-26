<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminBookingRequest;
use App\Http\Requests\Admin\UpdateAdminBookingRequest;
use App\Http\Requests\Admin\UploadBookingPhotoRequest;
use App\Models\Booking;
use App\Models\BookingPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;

class BookingController extends Controller
{
    /** Longest edge (px) for delivery photos — whole image scaled, never cropped. */
    private const MAX_DIMENSION = 2560;

    /** JPEG/WebP quality for compressed delivery photos. */
    private const QUALITY = 85;

    /** Free-tier ceiling for the storage meter (10 GB in bytes). */
    private const FREE_TIER_BYTES = 10 * 1024 * 1024 * 1024;

    public function index(Request $request)
    {
        $statusFilter = $request->query('status');

        $bookings = Booking::with('package')
            ->withCount('photos')
            ->when(
                $statusFilter && in_array($statusFilter, Booking::STATUSES, true),
                fn ($q) => $q->where('status', $statusFilter)
            )
            ->latest()
            ->get();

        $newCount = Booking::where('status', Booking::STATUS_NEW)->count();

        return view('admin.bookings.index', [
            'bookings' => $bookings,
            'newCount' => $newCount,
            'statusFilter' => $statusFilter,
            'currencies' => config('currencies.booking', []),
            'dialCodes' => config('countries.dial_codes', []),
        ]);
    }

    /**
     * Record an ad-hoc booking the admin handled outside the agency schedule
     * (already paid, already shot) so it lands in the system for delivery
     * and finance. Always stored as confirmed, never tied to a package.
     */
    public function store(StoreAdminBookingRequest $request): JsonResponse
    {
        $data = $request->validated();

        $booking = Booking::create([
            'source' => Booking::SOURCE_ADMIN,
            'status' => Booking::STATUS_CONFIRMED,
            'name' => $data['name'],
            'session_date' => $data['session_date'],
            'session_time' => $data['session_time'],
            'country_code' => $data['country_code'],
            'phone' => $data['phone'],
            'currency' => $data['currency'],
            'amount' => $data['amount'],
            'notes' => $data['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking '.$booking->booking_number.' recorded successfully.',
            'booking' => $this->serializeBooking($booking->fresh()),
        ]);
    }

    /**
     * Edit an admin-created booking. Status is changed through updateStatus()
     * only, and the booking number is never editable.
     */
    public function update(UpdateAdminBookingRequest $request, Booking $booking): JsonResponse
    {
        $data = $request->validated();

        $booking->update([
            'name' => $data['name'],
            'session_date' => $data['session_date'],
            'session_time' => $data['session_time'],
            'country_code' => $data['country_code'],
            'phone' => $data['phone'],
            'currency' => $data['currency'],
            'amount' => $data['amount'],
            'notes' => $data['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking updated successfully.',
            'booking' => $this->serializeBooking($booking->fresh()),
        ]);
    }

    public function show(Booking $booking)
    {
        $booking->load(['package', 'photos']);

        $photosJson = $booking->photos->map(fn (BookingPhoto $photo) => $this->serializePhoto($photo))->values();

        return view('admin.bookings.show', [
            'booking' => $booking,
            'photosJson' => $photosJson,
            'storage' => $this->storageMeter(),
            'whatsappMessage' => $booking->delivery_code ? $this->buildWhatsappMessage($booking) : '',
        ]);
    }

    public function updateStatus(Request $request, Booking $booking): JsonResponse
    {
        // Admin bookings only move between confirmed and completed.
        $request->validate([
            'status' => ['required', 'in:'.implode(',', $booking->allowedStatuses())],
        ]);

        $booking->update(['status' => $request->input('status')]);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
            'booking' => $this->serializeBooking($booking->fresh('package')),
        ]);
    }

    public function updateNotes(Request $request, Booking $booking): JsonResponse
    {
        $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $booking->update(['notes' => $request->input('notes')]);

        return response()->json([
            'success' => true,
            'message' => 'Notes saved successfully.',
            'booking' => $this->serializeBooking($booking->fresh('package')),
        ]);
    }

    /* ── Photo delivery ── */

    /**
     * Upload a single delivery photo: compress, push to R2, store the row.
     * Called sequentially (one file per request) by the admin uploader.
     */
    public function uploadPhoto(UploadBookingPhotoRequest $request, Booking $booking): JsonResponse
    {
        $file = $request->file('photo');

        // Defence in depth: verify the real MIME, not just the extension.
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (! in_array($file->getMimeType(), $allowedMimes, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Unsupported image type.',
            ], 422);
        }

        $manager = new ImageManager(new GdDriver);
        $image = $manager->read($file->getRealPath());

        // Scale the longest side down to MAX_DIMENSION (proportional, no crop,
        // no upscaling of already-smaller images).
        $image->scaleDown(width: self::MAX_DIMENSION, height: self::MAX_DIMENSION);

        // Keep the original format; just compress.
        $extension = match ($file->getMimeType()) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $encoded = match ($extension) {
            'png' => $image->toPng(),
            'webp' => $image->toWebp(quality: self::QUALITY),
            default => $image->toJpeg(quality: self::QUALITY),
        };

        $binary = (string) $encoded;
        $key = "bookings/{$booking->booking_number}/".Str::uuid()->toString().".{$extension}";

        Storage::disk('r2')->put($key, $binary);

        $maxSort = (int) $booking->photos()->max('sort_order');

        $photo = $booking->photos()->create([
            'r2_key' => $key,
            'original_name' => $file->getClientOriginalName(),
            'size' => strlen($binary),
            'sort_order' => $maxSort + 1,
        ]);

        return response()->json([
            'success' => true,
            'photo' => $this->serializePhoto($photo),
            'storage' => $this->storageMeter(),
        ]);
    }

    public function destroyPhoto(BookingPhoto $photo): JsonResponse
    {
        // Model "deleting" hook removes the object from R2.
        $photo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Photo deleted.',
            'storage' => $this->storageMeter(),
        ]);
    }

    /**
     * Generate (or regenerate) the delivery access code and return a ready-to-send
     * WhatsApp message containing the booking number, code, and delivery link.
     */
    public function generateDelivery(Booking $booking): JsonResponse
    {
        $booking->update(['delivery_code' => Booking::generateDeliveryCode()]);
        $booking->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Access code generated.',
            'code' => $booking->delivery_code,
            'whatsapp_message' => $this->buildWhatsappMessage($booking),
        ]);
    }

    private function buildWhatsappMessage(Booking $booking): string
    {
        $link = url($booking->my_photos_url);

        return "Hi {$booking->name}, your ".config('app.name')." photos are ready!\n\n"
            ."Open: {$link}\n"
            ."Booking number: {$booking->booking_number}\n"
            ."Access code: {$booking->delivery_code}\n\n"
            .'Enter both to view and download your photos. Thank you for choosing us!';
    }

    /* ── Serializers & helpers ── */

    private function serializePhoto(BookingPhoto $photo): array
    {
        return [
            'id' => $photo->id,
            'original_name' => $photo->original_name,
            'size' => $photo->size,
            'size_label' => $photo->size_label,
            'url' => $photo->download_url,
        ];
    }

    private function storageMeter(): array
    {
        $used = BookingPhoto::totalBytes();

        return [
            'used' => $used,
            'used_label' => BookingPhoto::formatBytes($used),
            'limit' => self::FREE_TIER_BYTES,
            'limit_label' => '10 GB',
            'percent' => min(100, round($used / self::FREE_TIER_BYTES * 100, 1)),
        ];
    }

    private function serializeBooking(Booking $booking): array
    {
        return [
            'id' => $booking->id,
            'booking_number' => $booking->booking_number,
            'source' => $booking->source,
            'source_label' => $booking->source_label,
            'is_admin_booking' => $booking->isAdminBooking(),
            'allowed_statuses' => $booking->allowedStatuses(),
            'name' => $booking->name,
            'phone' => $booking->phone,
            'country_code' => $booking->country_code,
            'full_phone' => $booking->full_phone,
            'email' => $booking->email,
            'status' => $booking->status,
            'status_label' => $booking->status_label,
            'status_color' => $booking->status_color,
            'currency' => $booking->currency,
            'amount' => $booking->amount,
            'amount_label' => $booking->amount_label,
            'notes' => $booking->notes,
            'package_id' => $booking->package_id,
            'package_name' => $booking->package?->name_en ?? '—',
            'session_date' => $booking->session_date?->format('Y-m-d'),
            'session_time' => $booking->session_time,
            'session_datetime_label' => $booking->session_datetime_label,
            'created_at' => $booking->created_at->format('M j, Y g:i A'),
        ];
    }
}
