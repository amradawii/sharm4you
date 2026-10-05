<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreReviewRequest;
use App\Http\Requests\Admin\UpdateReviewRequest;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

        $review = $this->saveReview([
            'name' => $data['name'],
            'country' => config('countries.list.'.$data['country_code']),
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

        $review = $this->saveReview([
            'name' => $data['name'],
            'country' => config('countries.list.'.$data['country_code']),
            'country_code' => $data['country_code'],
            'comment' => $data['comment'],
            'is_featured' => $data['is_featured'] ?? $review->is_featured,
            'sort_order' => $data['sort_order'] ?? $review->sort_order,
        ], $review);

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
        $review = $this->saveReview([], $review, toggleFeatured: true);

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

    private function saveReview(array $data, ?Review $review = null, bool $toggleFeatured = false): Review
    {
        return DB::transaction(function () use ($data, $review, $toggleFeatured) {
            $featuredIds = Review::featured()->orderBy('id')->lockForUpdate()->pluck('id');
            $review = $review ? Review::query()->lockForUpdate()->findOrFail($review->id) : new Review;
            $isFeatured = $toggleFeatured ? ! $review->is_featured : ($data['is_featured'] ?? $review->is_featured ?? false);

            if ($isFeatured && ! $review->is_featured && $featuredIds->count() >= Review::FEATURED_LIMIT) {
                throw ValidationException::withMessages([
                    'is_featured' => 'Featured limit reached ('.Review::FEATURED_LIMIT.'). Unfeature another review first.',
                ]);
            }

            $review->fill([...$data, 'is_featured' => $isFeatured])->save();

            return $review;
        }, 3);
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
