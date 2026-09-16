<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register_and_receive_an_authenticated_session(): void
    {
        $response = $this->postJson('/register', [
            'name' => 'Test User',
            'email' => 'USER@EXAMPLE.COM',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated();
        $this->assertAuthenticated();

        $user = User::query()->sole();

        $this->assertSame('user@example.com', $user->email);
        $this->assertSame(UserRole::User, $user->role);
        $this->assertNotNull($user->uuid);

        $this->getJson('/api/user')
            ->assertSuccessful()
            ->assertJsonPath('email', 'user@example.com')
            ->assertJsonPath('uuid', $user->uuid);
    }

    public function test_unconfigured_passkey_login_is_not_exposed(): void
    {
        $this->postJson('/passkeys/login')->assertMethodNotAllowed();
    }

    public function test_the_last_registration_email_is_remembered_in_the_session(): void
    {
        $this->postJson('/register', [
            'name' => '',
            'email' => 'new-user@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertUnprocessable();

        $this->assertSame(
            'new-user@example.com',
            session('auth.last_email'),
        );
    }
}
