<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_cannot_access_admin_routes(): void
    {
        $this->getJson('/api/admin/groups')
            ->assertUnauthorized();
    }

    public function test_a_regular_user_cannot_access_admin_routes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/admin/groups')
            ->assertForbidden();
    }

    public function test_an_admin_can_access_admin_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->getJson('/api/admin/groups')
            ->assertOk();
    }

    public function test_the_current_user_response_contains_the_role(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('role', UserRole::Admin->value);
    }
}
