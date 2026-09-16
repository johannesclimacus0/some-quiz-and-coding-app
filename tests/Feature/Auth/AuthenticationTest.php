<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_log_in_and_read_their_profile(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/login', [
            'email' => 'user@example.com',
            'password' => 'password',
            'remember' => true,
        ])->assertOk();

        $this->assertAuthenticatedAs($user);

        $this->getJson('/api/user')
            ->assertSuccessful()
            ->assertJsonPath('email', 'user@example.com');
    }

    public function test_invalid_credentials_do_not_create_a_session(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
            'remember' => false,
        ])->assertUnprocessable();

        $this->assertGuest();
    }

    public function test_the_last_login_email_is_remembered_in_the_session(): void
    {
        $this->postJson('/login', [
            'email' => 'remember-me@example.com',
            'password' => 'wrong-password',
            'remember' => false,
        ])->assertUnprocessable();

        $this->assertSame(
            'remember-me@example.com',
            session('auth.last_email'),
        );

        $this->get('/login')
            ->assertOk()
            ->assertSee('data-last-auth-email="remember-me@example.com"', false);
    }

    public function test_a_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/logout')
            ->assertNoContent();

        $this->assertGuest();
        $this->getJson('/api/user')->assertUnauthorized();
    }
}
