<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminProfileTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::create(['name' => 'Owner', 'username' => 'owner', 'email' => 'owner@example.test', 'password' => 'Current-Password-123!']);
        $this->actingAs($this->admin, 'admin');
    }

    public function test_profile_edits_are_validated_and_usernames_are_normalized(): void
    {
        $this->get('/admin/profile')->assertOk();
        $this->putJson('/admin/profile', ['name' => 'Updated Owner', 'username' => ' New_Owner ', 'email' => 'updated@example.test'])
            ->assertOk()->assertJsonPath('message', 'Profile updated successfully.');
        $this->assertSame('new_owner', $this->admin->fresh()->username);
        $this->assertSame('updated@example.test', $this->admin->fresh()->email);
    }

    public function test_other_admin_identity_cannot_be_reused(): void
    {
        Admin::create(['name' => 'Other', 'username' => 'other', 'email' => 'other@example.test', 'password' => 'Other-Password-123!']);
        $this->putJson('/admin/profile', ['name' => 'Owner', 'username' => 'OTHER', 'email' => 'other@example.test'])
            ->assertStatus(422)->assertJsonValidationErrors(['username', 'email']);
        $this->assertSame('owner', $this->admin->fresh()->username);
    }

    public function test_malformed_profile_fields_are_validation_errors(): void
    {
        $this->putJson('/admin/profile', ['name' => ['bad'], 'username' => ['bad'], 'email' => ['bad']])
            ->assertStatus(422)->assertJsonValidationErrors(['name', 'username', 'email']);
    }

    public function test_incorrect_current_password_does_not_change_the_hash(): void
    {
        $before = $this->admin->password;
        $this->putJson('/admin/profile/password', ['current_password' => 'Wrong-password-123!', 'password' => 'New-Password-456!', 'password_confirmation' => 'New-Password-456!'])
            ->assertStatus(422)->assertJsonPath('errors.current_password.0', 'The current password is incorrect.');
        $this->assertSame($before, $this->admin->fresh()->password);
    }

    public function test_password_strength_and_confirmation_are_enforced(): void
    {
        foreach ([['weak', 'weak'], ['New-Password-456!', 'Different-Password-456!']] as [$password, $confirmation]) {
            $this->putJson('/admin/profile/password', ['current_password' => 'Current-Password-123!', 'password' => $password, 'password_confirmation' => $confirmation])
                ->assertStatus(422)->assertJsonValidationErrors(['password']);
        }
        $this->assertTrue(Hash::check('Current-Password-123!', $this->admin->fresh()->password));
    }

    public function test_new_bcrypt_passwords_cannot_silently_truncate_after_72_bytes(): void
    {
        $before = $this->admin->password;
        foreach ([str_repeat('a', 73).'A1!', str_repeat('é', 37).'A1!'] as $password) {
            $this->putJson('/admin/profile/password', ['current_password' => 'Current-Password-123!', 'password' => $password, 'password_confirmation' => $password])
                ->assertStatus(422)->assertJsonValidationErrors(['password']);
        }
        $this->assertSame($before, $this->admin->fresh()->password);
    }

    public function test_password_array_inputs_are_validation_errors(): void
    {
        $this->putJson('/admin/profile/password', ['current_password' => ['bad'], 'password' => ['bad'], 'password_confirmation' => ['bad']])
            ->assertStatus(422)->assertJsonValidationErrors(['current_password', 'password']);
    }

    public function test_successful_password_change_is_hashed(): void
    {
        $this->putJson('/admin/profile/password', ['current_password' => 'Current-Password-123!', 'password' => 'New-Password-456!', 'password_confirmation' => 'New-Password-456!'])
            ->assertOk()->assertJsonPath('message', 'Password updated successfully.');
        $this->assertTrue(Hash::check('New-Password-456!', $this->admin->fresh()->password));
        $this->assertFalse(Hash::check('Current-Password-123!', $this->admin->fresh()->password));
    }
}
