<?php

namespace App\Http\Controllers;

use App\Http\Requests\LookupPhotosRequest;
use App\Models\Booking;
use Illuminate\Http\Request;

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
     * Resolve and validate a booking from booking_number + delivery_code.
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
