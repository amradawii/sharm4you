<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreReviewRequest;
use App\Http\Requests\Admin\UpdateReviewRequest;
use App\Models\Review;
use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    public function index()
    {
        $reviews = Review::ordered()->get();
        $reviewsJson = $reviews->map(fn (Review $review) => $this->serializeReview($review))->values();

        $countries = config('countries.list', []);

        return view('admin.reviews.index', compact('reviewsJson', 'countries'));
    }

    public function store(StoreReviewRequest $request): JsonResponse
    {
        $data = $request->validated();
        $sortOrder = $data['sort_order'] ?? ((int) Review::max('sort_order') + 1);

        $review = Review::create([
            'name' => $data['name'],
            'country' => $data['country'],
            'country_code' => $data['country_code'],
            'comment' => $data['comment'],
            'status' => $data['status'],
            'is_featured' => $data['is_featured'] ?? false,
            'sort_order' => $sortOrder,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review created successfully.',
            'review' => $this->serializeReview($review->fresh()),
        ]);
    }

    public function update(UpdateReviewRequest $request, Review $review): JsonResponse
    {
        $data = $request->validated();

        $review->update([
            'name' => $data['name'],
            'country' => $data['country'],
            'country_code' => $data['country_code'],
            'comment' => $data['comment'],
            'is_featured' => $data['is_featured'] ?? $review->is_featured,
            'sort_order' => $data['sort_order'] ?? $review->sort_order,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review updated successfully.',
            'review' => $this->serializeReview($review->fresh()),
        ]);
    }

    public function setStatus(Review $review): JsonResponse
    {
        $status = request()->input('status');
        if (! in_array($status, Review::STATUSES, true)) {
            return response()->json(['success' => false, 'message' => 'Invalid status.'], 422);
        }

        $review->update(['status' => $status]);

        return response()->json([
            'success' => true,
            'message' => 'Review status updated.',
            'review' => $this->serializeReview($review->fresh()),
        ]);
    }

    public function toggleFeatured(Review $review): JsonResponse
    {
        if (! $review->is_featured) {
            $featuredCount = Review::featured()->count();
            if ($featuredCount >= Review::FEATURED_LIMIT) {
                return response()->json([
                    'success' => false,
                    'message' => 'Featured limit reached ('.Review::FEATURED_LIMIT.'). Unfeature another review first.',
                ], 422);
            }
        }

        $review->update(['is_featured' => ! $review->is_featured]);

        return response()->json([
            'success' => true,
            'message' => $review->is_featured ? 'Review is now featured on home.' : 'Review removed from home.',
            'review' => $this->serializeReview($review->fresh()),
        ]);
    }

    public function destroy(Review $review): JsonResponse
    {
        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully.',
        ]);
    }

    private function serializeReview(Review $review): array
    {
        return [
            'id' => $review->id,
            'name' => $review->name,
            'country' => $review->country,
            'country_code' => $review->country_code,
            'flag' => $review->flag,
            'comment' => $review->comment,
            'status' => $review->status,
            'status_label' => $review->status_label,
            'status_color' => $review->status_color,
            'is_featured' => (bool) $review->is_featured,
            'sort_order' => (int) $review->sort_order,
            'created_at' => $review->created_at?->format('M j, Y'),
        ];
    }
}
