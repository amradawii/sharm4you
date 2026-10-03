<?php

namespace Tests\Feature;

use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Happy Tourist',
            'country_code' => 'ru',
            'comment' => 'Amazing photography session, highly recommend!',
        ], $overrides);
    }

    public function test_valid_review_is_stored_as_pending(): void
    {
        $this->postJson(route('review.store'), $this->validPayload())
            ->assertOk()
            ->assertJson(['success' => true]);

        $review = Review::sole();

        $this->assertSame(Review::STATUS_PENDING, $review->status);
        $this->assertSame('RU', $review->country_code);
        $this->assertSame('Russia', $review->country);
    }

    public function test_missing_required_fields_are_rejected(): void
    {
        $this->postJson(route('review.store'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'country_code', 'comment']);
    }

    public function test_short_comment_is_rejected(): void
    {
        $this->postJson(route('review.store'), $this->validPayload(['comment' => 'short']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['comment']);
    }

    public function test_invalid_country_code_is_rejected(): void
    {
        $this->postJson(route('review.store'), $this->validPayload(['country_code' => 'ZZ']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['country_code']);
    }

    public function test_country_code_is_normalized_to_uppercase(): void
    {
        $this->postJson(route('review.store'), $this->validPayload(['country_code' => 'it']))
            ->assertOk();

        $this->assertSame('IT', Review::sole()->country_code);
        $this->assertSame('Italy', Review::sole()->country);
    }
}
