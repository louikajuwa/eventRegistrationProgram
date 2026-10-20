<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('Sign in');
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@mlm.test',
            'password' => 'password',
            'is_active' => true,
        ]);

        $response = $this->postJson('/login', [
            'email' => 'admin@mlm.test',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.redirect', route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'admin@mlm.test',
            'password' => 'password',
        ]);

        $this->postJson('/login', [
            'email' => 'admin@mlm.test',
            'password' => 'wrong',
        ])->assertStatus(422)->assertJsonValidationErrorFor('email');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'off@mlm.test',
            'password' => 'password',
            'is_active' => false,
        ]);

        $this->postJson('/login', [
            'email' => 'off@mlm.test',
            'password' => 'password',
        ])->assertStatus(422);

        $this->assertGuest();
    }

    public function test_authenticated_user_can_reach_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee($user->name);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/logout')->assertOk();
        $this->assertGuest();
    }

    public function test_deactivated_user_with_active_session_is_logged_out_on_next_request(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);
        $user->update(['is_active' => false]);

        $this->getJson('/dashboard')->assertStatus(401);
        $this->assertGuest();
    }

    public function test_deactivated_user_is_redirected_to_login_for_web_requests(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);
        $user->update(['is_active' => false]);

        $this->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_password_change_requires_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->actingAs($user)->putJson('/profile/password', [
            'current_password' => 'not-the-password',
            'password' => 'NewSecret123!',
            'password_confirmation' => 'NewSecret123!',
        ])->assertStatus(422)->assertJsonValidationErrors(['current_password']);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_password_change_succeeds_with_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->actingAs($user)->putJson('/profile/password', [
            'current_password' => 'password',
            'password' => 'NewSecret123!',
            'password_confirmation' => 'NewSecret123!',
        ])->assertOk();

        $this->assertTrue(Hash::check('NewSecret123!', $user->fresh()->password));
    }

    public function test_profile_email_change_requires_current_password(): void
    {
        $user = User::factory()->create(['email' => 'old@mlm.test', 'password' => 'password']);

        $this->actingAs($user)->putJson('/profile', [
            'name' => $user->name,
            'email' => 'new@mlm.test',
            'current_password' => 'wrong',
        ])->assertStatus(422)->assertJsonValidationErrors(['current_password']);

        $this->assertSame('old@mlm.test', $user->fresh()->email);
    }

    public function test_forgot_password_is_throttled(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/forgot-password', ['email' => 'nobody@mlm.test'])->assertStatus(422);
        }

        $this->postJson('/forgot-password', ['email' => 'nobody@mlm.test'])->assertStatus(429);
    }

    public function test_register_is_throttled(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/register', [])->assertStatus(422);
        }

        $this->postJson('/register', [])->assertStatus(429);
    }
}
