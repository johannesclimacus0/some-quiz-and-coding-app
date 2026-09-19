<?php

namespace Tests\Feature\Group;

use App\Models\Group;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_and_quizzes_can_belong_to_multiple_groups(): void
    {
        $user = User::factory()->create();
        $groups = Group::factory()->count(2)->create();
        $quizzes = Quiz::factory()->count(2)->create();

        $groups[0]->users()->attach($user);
        $groups[1]->users()->attach($user);
        $groups[0]->quizzes()->attach($quizzes);
        $groups[1]->quizzes()->attach($quizzes[0]);

        $this->assertCount(2, $user->groups);
        $this->assertCount(2, $groups[0]->quizzes);
        $this->assertCount(2, $quizzes[0]->groups);
    }

    public function test_soft_deleting_a_group_preserves_its_assignments(): void
    {
        $group = Group::factory()->create();
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();

        $group->users()->attach($user);
        $group->quizzes()->attach($quiz);
        $group->delete();

        $this->assertSoftDeleted($group);
        $this->assertDatabaseHas('group_user', [
            'group_id' => $group->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('group_quiz', [
            'group_id' => $group->id,
            'quiz_id' => $quiz->id,
        ]);
    }
}
