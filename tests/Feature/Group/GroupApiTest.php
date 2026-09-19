<?php

namespace Tests\Feature\Group;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_groups(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $uuid = $this->postJson('/api/admin/groups', ['name' => 'Backend'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Backend')
            ->assertJsonPath('data.users_count', 0)
            ->assertJsonPath('data.quizzes_count', 0)
            ->json('data.uuid');

        $url = '/api/admin/groups/' . $uuid;

        $this->getJson('/api/admin/groups')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.uuid', $uuid);
        $this->getJson($url)->assertOk()->assertJsonPath('data.name', 'Backend');
        $this->patchJson($url, ['name' => 'Platform'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Platform');

        $this->deleteJson($url)->assertNoContent();
        $this->assertSoftDeleted('groups', ['uuid' => $uuid]);
        $this->getJson($url)->assertNotFound();
    }

    public function test_group_name_is_required_and_unique(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $group = Group::factory()->create(['name' => 'Existing']);

        $this->postJson('/api/admin/groups', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
        $this->postJson('/api/admin/groups', ['name' => 'Existing'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
        $this->patchJson('/api/admin/groups/' . $group->uuid, ['name' => 'Existing'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Existing');
    }

    public function test_guests_and_regular_users_cannot_manage_groups(): void
    {
        $group = Group::factory()->create();

        $this->getJson('/api/admin/groups')->assertUnauthorized();
        $this->actingAs(User::factory()->create());
        $this->getJson('/api/admin/groups')->assertForbidden();
        $this->postJson('/api/admin/groups', ['name' => 'Forbidden'])->assertForbidden();
        $this->patchJson('/api/admin/groups/' . $group->uuid, ['name' => 'Forbidden'])->assertForbidden();
        $this->deleteJson('/api/admin/groups/' . $group->uuid)->assertForbidden();
    }
}
