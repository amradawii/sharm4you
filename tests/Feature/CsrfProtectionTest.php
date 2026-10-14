<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CsrfProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_mutations_reject_missing_csrf_tokens_outside_the_test_bypass(): void
    {
        $this->app->instance('env', 'production');
        foreach (['/book-session', '/review', '/my-photos', '/ru/my-photos'] as $path) {
            $this->postJson($path, [])->assertStatus(419);
        }
    }

    public function test_authenticated_admin_mutations_still_require_csrf(): void
    {
        $this->actingAs(Admin::create(['name' => 'Owner', 'username' => 'owner', 'email' => 'owner@example.test', 'password' => 'Correct-password-123!']), 'admin');
        $this->app->instance('env', 'production');
        $this->putJson('/admin/profile', [])->assertStatus(419);
    }

    public function test_valid_csrf_token_reaches_normal_validation(): void
    {
        $this->app->instance('env', 'production');
        $this->withSession(['_token' => 'test-csrf-token']);
        $this->postJson('/book-session', [], ['X-CSRF-TOKEN' => 'test-csrf-token'])->assertStatus(422);
    }
}
