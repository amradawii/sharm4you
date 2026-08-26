<?php

namespace App\Http\Controllers;

use App\Http\Requests\LookupPhotosRequest;
use App\Models\Booking;

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
        $number = $request->input('booking_number');
        $code = $request->input('delivery_code');

        $booking = Booking::where('booking_number', $number)
            ->whereNotNull('delivery_code')
            ->first();

        // Constant-ish check: only match when a code exists and equals input.
        $valid = $booking
            && $booking->delivery_code
            && hash_equals($booking->delivery_code, $code)
            && $booking->photos()->exists();

        if (! $valid) {
            return back()
                ->withInput($request->only('booking_number'))
                ->with('lookup_error', __('site.my_photos.not_found'));
        }

        // Fresh 24h presigned URLs are generated on-demand for each photo.
        $photos = $booking->photos()->get();

        return view('my-photos', [
            'seoKey' => 'my_photos',
            'booking' => $booking,
            'photos' => $photos,
            'submitted' => true,
        ]);
    }
}
