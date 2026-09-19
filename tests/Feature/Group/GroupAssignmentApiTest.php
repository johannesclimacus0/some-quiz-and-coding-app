<?php

namespace Tests\Feature\Group;

use App\Models\Group;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupAssignmentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_and_remove_regular_users_idempotently(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $group = Group::factory()->create();
        $user = User::factory()->create(['name' => 'Linus Example', 'email' => 'linus@example.org']);
        User::factory()->create(['name' => 'Another User']);
        $url = '/api/admin/groups/' . $group->uuid . '/users/' . $user->uuid;

        $this->getJson('/api/admin/groups/' . $group->uuid . '/users?search=linus')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.uuid', $user->uuid)
            ->assertJsonPath('data.0.assigned', false);

        $this->putJson($url)->assertOk()->assertJsonPath('data.assigned', true);
        $this->putJson($url)->assertOk()->assertJsonPath('data.assigned', true);
        $this->assertDatabaseCount('group_user', 1);

        $this->deleteJson($url)->assertNoContent();
        $this->deleteJson($url)->assertNoContent();
        $this->assertDatabaseCount('group_user', 0);
    }

    public function test_admin_cannot_be_added_to_a_group(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $group = Group::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->putJson('/api/admin/groups/' . $group->uuid . '/users/' . $admin->uuid)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('user');
        $this->assertDatabaseCount('group_user', 0);
    }

    public function test_admin_can_assign_and_unassign_quizzes_idempotently(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $group = Group::factory()->create();
        $quiz = Quiz::factory()->create(['title' => 'PostgreSQL internals']);
        Quiz::factory()->create(['title' => 'Other quiz']);
        $url = '/api/admin/groups/' . $group->uuid . '/quizzes/' . $quiz->uuid;

        $this->getJson('/api/admin/groups/' . $group->uuid . '/quizzes?search=postgresql')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.uuid', $quiz->uuid)
            ->assertJsonPath('data.0.assigned', false);

        $this->putJson($url)->assertOk()->assertJsonPath('data.assigned', true);
        $this->putJson($url)->assertOk()->assertJsonPath('data.assigned', true);
        $this->assertDatabaseCount('group_quiz', 1);

        $this->deleteJson($url)->assertNoContent();
        $this->deleteJson($url)->assertNoContent();
        $this->assertDatabaseCount('group_quiz', 0);
    }

    public function test_regular_users_cannot_manage_group_assignments(): void
    {
        $this->actingAs(User::factory()->create());
        $group = Group::factory()->create();
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();

        $this->getJson('/api/admin/groups/' . $group->uuid . '/users')->assertForbidden();
        $this->putJson('/api/admin/groups/' . $group->uuid . '/users/' . $user->uuid)->assertForbidden();
        $this->getJson('/api/admin/groups/' . $group->uuid . '/quizzes')->assertForbidden();
        $this->putJson('/api/admin/groups/' . $group->uuid . '/quizzes/' . $quiz->uuid)->assertForbidden();
    }
}
