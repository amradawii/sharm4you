<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_unrelated_public_operations_have_independent_limits(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/book-session', [])->assertStatus(422);
        }
        $this->postJson('/book-session', [])->assertStatus(429)->assertHeader('Retry-After');
        $this->postJson('/review', [])->assertStatus(422);
        $this->postJson('/my-photos', [])->assertStatus(422);
        $this->postJson('/my-photos/download-all', [])->assertStatus(422);
    }

    public function test_a_limit_is_shared_across_locales(): void
    {
        foreach (['/review', '/ru/review', '/it/review'] as $path) {
            $this->postJson($path, [])->assertStatus(422);
        }
        $this->postJson('/tr/review', [])->assertStatus(429);
    }
}
