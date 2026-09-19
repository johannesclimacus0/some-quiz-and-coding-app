<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizAttempt>
 */
class QuizAttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'quiz_id' => Quiz::factory(),
            'snapshot' => [
                'quiz' => ['uuid' => fake()->uuid(), 'title' => fake()->sentence(), 'description' => null],
                'questions' => [],
            ],
            'started_at' => now(),
            'submitted_at' => null,
            'correct_answers' => null,
            'total_questions' => 0,
        ];
    }
}
