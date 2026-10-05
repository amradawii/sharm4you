<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Admin
    {
        return Admin::create(['name' => 'Owner', 'username' => 'owner', 'email' => 'owner@example.test', 'password' => 'Correct-password-123!']);
    }

    public function test_login_success_and_failure_messages_are_english(): void
    {
        $admin = $this->admin();
        $this->postJson('/admin/login', ['username' => 'owner', 'password' => 'wrong-password'])
            ->assertUnauthorized()->assertJsonPath('message', 'The provided credentials are incorrect.');
        $this->postJson('/admin/login', ['username' => ' OWNER ', 'password' => 'Correct-password-123!'])
            ->assertOk()->assertJsonPath('message', 'Welcome back, Owner.');
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_malformed_username_is_a_validation_error(): void
    {
        $this->postJson('/admin/login', ['username' => ['bad'], 'password' => 'wrong-password'])
            ->assertStatus(422)->assertJsonValidationErrors(['username']);
    }

    public function test_username_casing_cannot_reset_the_limit(): void
    {
        $this->admin();
        foreach (['owner', 'OWNER', 'Owner', 'oWner', 'owNER'] as $username) {
            $this->postJson('/admin/login', ['username' => $username, 'password' => 'wrong-password'])->assertUnauthorized();
        }
        $this->postJson('/admin/login', ['username' => 'OwNeR', 'password' => 'wrong-password'])
            ->assertStatus(429)->assertHeader('Retry-After');
    }

    public function test_login_has_an_ip_wide_backstop(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->postJson('/admin/login', ['username' => 'unknown_'.$i, 'password' => 'wrong-password'])->assertUnauthorized();
        }
        $this->postJson('/admin/login', ['username' => 'another_unknown', 'password' => 'wrong-password'])
            ->assertStatus(429)->assertHeader('Retry-After');
    }

    public function test_admin_validation_is_english_after_public_locale_changes(): void
    {
        $this->app->setLocale('ru');
        $this->postJson('/admin/login', [])->assertStatus(422)
            ->assertJsonPath('errors.username.0', 'Username is required.');
        $this->assertSame('en', app()->getLocale());
    }

    public function test_login_rotates_the_session_identifier_and_logout_invalidates_it(): void
    {
        $this->admin();
        $this->startSession();
        $id = session()->getId();
        session()->save();
        $this->withCookie(config('session.cookie'), $id);
        $this->postJson('/admin/login', ['username' => 'owner', 'password' => 'Correct-password-123!'])->assertOk();
        $this->assertNotSame($id, session()->getId());
        $this->assertAuthenticated('admin');
        $token = session()->token();
        $id = session()->getId();
        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest('admin');
        $this->assertNotSame($id, session()->getId());
        $this->assertNotSame($token, session()->token());
    }

    public function test_login_limit_expires_and_valid_input_can_retry(): void
    {
        $this->admin();
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/admin/login', ['username' => 'owner', 'password' => 'wrong-password'])->assertUnauthorized();
        }
        $this->postJson('/admin/login', ['username' => 'owner', 'password' => 'Correct-password-123!'])->assertStatus(429);
        $this->travel(61)->seconds();
        $this->postJson('/admin/login', ['username' => 'owner', 'password' => 'Correct-password-123!'])->assertOk();
    }

    public function test_oversized_login_fields_are_validation_errors(): void
    {
        $this->postJson('/admin/login', ['username' => str_repeat('a', 51), 'password' => str_repeat('a', 256)])
            ->assertStatus(422)->assertJsonValidationErrors(['username', 'password']);
    }

    public function test_admin_usernames_are_stored_canonically(): void
    {
        $admin = new Admin(['username' => ' MiXeD_Name ']);
        $this->assertSame('mixed_name', $admin->username);
    }
}
