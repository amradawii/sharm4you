<?php

namespace App\Http\Controllers;

use App\Http\Requests\LookupPhotosRequest;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

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
    public function downloadAll(Request $request): BinaryFileResponse|JsonResponse
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

        // 30 sequential R2 fetches can exceed PHP's default 30s timeout if R2
        // is slow. Bump it for this request only — not a queue/job, just a guard.
        set_time_limit(120);

        $zipPath = tempnam(sys_get_temp_dir(), 'sharm4you_');
        $zipPath .= '.zip';

        try {
            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Could not create zip archive.');
            }

            foreach ($photos as $photo) {
                $content = Storage::disk('r2')->get($photo->r2_key);
                if ($content === false) {
                    continue;
                }
                $zip->addFromString($photo->original_name, $content);
            }

            $zip->close();

            return response()
                ->download($zipPath, $booking->booking_number.'-photos.zip')
                ->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            if (file_exists($zipPath)) {
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
        $number = strtoupper(trim((string) $request->input('booking_number')));
        $code = strtoupper(trim((string) $request->input('delivery_code')));

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
