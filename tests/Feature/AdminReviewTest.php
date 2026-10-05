<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(Admin::create([
            'name' => 'Owner', 'username' => 'owner', 'email' => 'owner@example.test', 'password' => 'secret-password',
        ]), 'admin');
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Tourist', 'country_code' => 'it', 'country' => 'Untrusted country',
            'comment' => 'Una bellissima esperienza fotografica.', 'status' => 'approved', 'is_featured' => false,
        ], $overrides);
    }

    private function review(bool $featured = false): Review
    {
        return Review::create([
            'name' => 'Tourist', 'country' => 'Italy', 'country_code' => 'IT',
            'comment' => 'A wonderful photography experience.', 'status' => 'approved', 'is_featured' => $featured,
        ]);
    }

    public function test_review_status_delete_and_malformed_inputs_have_safe_responses(): void
    {
        $review = $this->review();
        $this->patchJson('/admin/reviews/'.$review->id.'/status', ['status' => 'rejected'])->assertOk()->assertJsonPath('review.status', 'rejected');
        $this->patchJson('/admin/reviews/'.$review->id.'/status', ['status' => ['approved']])->assertStatus(422);
        $this->assertSame('rejected', $review->fresh()->status);
        $this->postJson('/admin/reviews', $this->payload(['country_code' => ['IT'], 'comment' => ['bad']]))
            ->assertStatus(422)->assertJsonValidationErrors(['country_code', 'comment']);
        $this->deleteJson('/admin/reviews/'.$review->id)->assertOk();
        $this->assertNull($review->fresh());
    }

    private function fillFeatured(): void
    {
        for ($i = 0; $i < Review::FEATURED_LIMIT; $i++) {
            $this->review(true);
        }
    }

    public function test_admin_can_create_a_review_with_server_derived_country(): void
    {
        $this->postJson('/admin/reviews', $this->payload())->assertOk()
            ->assertJsonPath('review.country', 'Italy')->assertJsonPath('review.country_code', 'IT')
            ->assertJsonPath('review.comment', 'Una bellissima esperienza fotografica.');
    }

    public function test_admin_can_edit_country_and_comment_without_changing_status(): void
    {
        $review = $this->review();
        $this->putJson('/admin/reviews/'.$review->id, $this->payload(['country_code' => 'ru', 'status' => 'rejected']))
            ->assertOk()->assertJsonPath('review.country', 'Russia')->assertJsonPath('review.status', 'approved');
    }

    public function test_create_cannot_bypass_the_featured_limit(): void
    {
        $this->fillFeatured();
        $this->postJson('/admin/reviews', $this->payload(['is_featured' => true]))
            ->assertStatus(422)->assertJsonValidationErrors(['is_featured']);
        $this->assertSame(Review::FEATURED_LIMIT, Review::count());
    }

    public function test_edit_cannot_bypass_the_featured_limit(): void
    {
        $this->fillFeatured();
        $review = $this->review();
        $this->putJson('/admin/reviews/'.$review->id, $this->payload(['name' => 'Changed', 'is_featured' => true]))
            ->assertStatus(422)->assertJsonValidationErrors(['is_featured']);
        $this->assertFalse($review->fresh()->is_featured);
        $this->assertSame('Tourist', $review->fresh()->name);
    }

    public function test_existing_featured_review_can_be_edited_at_capacity(): void
    {
        $this->fillFeatured();
        $review = Review::first();
        $this->putJson('/admin/reviews/'.$review->id, $this->payload(['name' => 'Changed', 'is_featured' => true]))
            ->assertOk()->assertJsonPath('review.name', 'Changed');
        $this->assertSame(Review::FEATURED_LIMIT, Review::featured()->count());
    }

    public function test_nonfeatured_reviews_remain_allowed_at_capacity(): void
    {
        $this->fillFeatured();
        $this->postJson('/admin/reviews', $this->payload())->assertOk();
        $this->assertSame(Review::FEATURED_LIMIT, Review::featured()->count());
    }

    public function test_toggle_returns_a_field_error_at_capacity_and_can_reuse_a_freed_slot(): void
    {
        $this->fillFeatured();
        $review = $this->review();
        $this->patchJson('/admin/reviews/'.$review->id.'/featured')->assertStatus(422)->assertJsonValidationErrors(['is_featured']);
        $this->patchJson('/admin/reviews/'.Review::featured()->first()->id.'/featured')->assertOk();
        $this->patchJson('/admin/reviews/'.$review->id.'/featured')->assertOk()->assertJsonPath('review.is_featured', true);
        $this->assertSame(Review::FEATURED_LIMIT, Review::featured()->count());
    }
}
