<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Models\Review;
use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    public function store(StoreReviewRequest $request): JsonResponse
    {
        Review::create([
            'name' => $request->input('name'),
            'country' => $request->input('country'),
            'country_code' => $request->input('country_code'),
            'comment' => $request->input('comment'),
            'status' => Review::STATUS_PENDING,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('site.testimonials.success'),
        ]);
    }
}
