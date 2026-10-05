<?php

namespace App\Http\Controllers;

use App\Http\Requests\LookupPhotosRequest;
use App\Models\Booking;
use App\Support\DeliveryArchive;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PhotoDeliveryController extends Controller
{
    public function form()
    {
        return view('my-photos', [
            'seoKey' => 'my_photos',
            'booking' => null,
            'photos' => collect(),
            'submitted' => false,
        ]);
    }

    public function show(LookupPhotosRequest $request)
    {
        $booking = $this->resolveBooking($request);

        if (! $booking) {
            return back()
                ->withInput($request->only('booking_number'))
                ->with('lookup_error', __('site.my_photos.not_found'));
        }

        // Fresh 24h presigned URLs are generated on-demand for each photo.
        $photos = $booking->photos()->ordered()->get();

        return view('my-photos', [
            'seoKey' => 'my_photos',
            'booking' => $booking,
            'photos' => $photos,
            'submitted' => true,
        ]);
    }

    /**
     * Generate a single zip of all delivery photos and stream it to the user.
     * The temp file is deleted automatically after the response finishes
     * sending — no cron, no persistence, nothing left on the shared host.
     */
    public function downloadAll(LookupPhotosRequest $request, DeliveryArchive $archive): BinaryFileResponse|JsonResponse
    {
        $booking = $this->resolveBooking($request);

        if (! $booking) {
            return response()->json([
                'success' => false,
                'message' => __('site.my_photos.not_found'),
            ], 422);
        }

        $photos = $booking->photos()->ordered()->get();

        if ($photos->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => __('site.my_photos.not_found'),
            ], 422);
        }

        // 50 sequential R2 fetches can exceed PHP's default 30s timeout if R2
        // is slow. Bump it for this request only — not a queue/job, just a guard.
        set_time_limit(120);

        $zipPath = $archive->create($photos);
        clearstatcache(true, $zipPath);

        try {
            return response()->download($zipPath, $booking->booking_number.'-photos.zip')->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            if (is_file($zipPath)) {
                @unlink($zipPath);
            }
            throw $e;
        }
    }

    /**
     * Resolve and validate a booking from booking_number + delivery_code.
     * Shared by show() and downloadAll() so the lookup logic never drifts.
     */
    private function resolveBooking(Request $request): ?Booking
    {
        if (! is_string($request->input('booking_number')) || ! is_string($request->input('delivery_code'))) {
            return null;
        }

        $number = strtoupper(trim($request->input('booking_number')));
        $code = strtoupper(trim($request->input('delivery_code')));

        if (! $number || ! $code) {
            return null;
        }

        $booking = Booking::where('booking_number', $number)
            ->whereNotNull('delivery_code')
            ->first();

        if (! $booking || ! $booking->delivery_code) {
            return null;
        }

        // Constant-time comparison to avoid timing leaks on the code.
        if (! hash_equals($booking->delivery_code, $code)) {
            return null;
        }

        if (! $booking->photos()->exists()) {
            return null;
        }

        return $booking;
    }
}
