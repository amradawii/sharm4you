<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;

class BookSessionController extends Controller
{
    public function store(StoreBookingRequest $request): JsonResponse
    {
        $countryCode = $request->input('country_code');
        if (! str_starts_with($countryCode, '+')) {
            $countryCode = '+'.$countryCode;
        }

        Booking::create([
            'package_id' => $request->input('package_id'),
            'session_date' => $request->input('session_date'),
            'session_time' => $request->input('session_time'),
            'name' => $request->input('name'),
            'phone' => $request->validated('phone'),
            'country_code' => $countryCode,
            'email' => $request->input('email'),
            'status' => Booking::STATUS_NEW,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('site.booking.success'),
        ]);
    }
}
