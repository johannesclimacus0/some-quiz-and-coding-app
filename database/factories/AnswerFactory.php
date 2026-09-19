<?php

namespace Database\Factories;

use App\Models\Answer;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Answer>
 */
class AnswerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'text' => fake()->sentence(),
            'is_correct' => false,
            'position' => fake()->numberBetween(0, 5),
        ];
    }

    public function correct(): static
    {
        return $this->state(fn () => [
            'is_correct' => true,
        ]);
    }
}
