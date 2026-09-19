<?php

namespace Tests\Feature\Quiz;

use App\Models\Group;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserQuizApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_sees_unique_quizzes_assigned_through_active_groups(): void
    {
        $user = User::factory()->create();
        $firstGroup = Group::factory()->create();
        $secondGroup = Group::factory()->create();
        $deletedGroup = Group::factory()->create();
        $sharedQuiz = Quiz::factory()->create(['title' => 'Shared quiz']);
        $expiredQuiz = Quiz::factory()->create(['title' => 'Expired quiz', 'due_at' => now()->subDay()]);
        $unassignedQuiz = Quiz::factory()->create(['title' => 'Draft quiz']);
        $deletedQuiz = Quiz::factory()->create(['title' => 'Deleted quiz']);
        $hiddenQuiz = Quiz::factory()->create(['title' => 'Hidden by group']);

        $user->groups()->attach([$firstGroup->id, $secondGroup->id, $deletedGroup->id]);
        $firstGroup->quizzes()->attach([$sharedQuiz->id, $expiredQuiz->id]);
        $secondGroup->quizzes()->attach($sharedQuiz);
        $firstGroup->quizzes()->attach($deletedQuiz);
        $deletedGroup->quizzes()->attach($hiddenQuiz);
        $deletedQuiz->delete();
        $deletedGroup->delete();

        $response = $this->actingAs($user)->getJson('/api/quizzes')
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonFragment(['uuid' => $sharedQuiz->uuid, 'title' => 'Shared quiz'])
            ->assertJsonFragment(['uuid' => $expiredQuiz->uuid, 'title' => 'Expired quiz'])
            ->assertJsonMissing(['uuid' => $unassignedQuiz->uuid])
            ->assertJsonMissing(['uuid' => $deletedQuiz->uuid])
            ->assertJsonMissing(['uuid' => $hiddenQuiz->uuid]);

        $response->assertJsonMissingPath('data.0.questions');
        $response->assertJsonMissingPath('data.0.answers');
        $response->assertJsonMissingPath('data.0.is_correct');
    }

    public function test_guest_cannot_list_assigned_quizzes(): void
    {
        $this->getJson('/api/quizzes')->assertUnauthorized();
    }

    public function test_quiz_details_do_not_expose_a_readiness_flag(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create();
        $quiz = Quiz::factory()->create();

        $user->groups()->attach($group);
        $group->quizzes()->attach($quiz);

        $this->actingAs($user)
            ->getJson("/api/quizzes/{$quiz->uuid}")
            ->assertOk()
            ->assertJsonMissingPath('data.ready');
    }
}
