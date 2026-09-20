<?php

namespace App\Services\QuizAttempts;

use App\Contracts\QuizSnapshotFactory;
use App\Models\Quiz;
use App\Services\Questions\QuestionTypeRegistry;

final class QuizSnapshot implements QuizSnapshotFactory
{
    public function __construct(
        private QuestionTypeRegistry $questionTypes,
    ) {}

    public function make(Quiz $quiz): array
    {
        $questions = [];

        foreach ($quiz->questions as $question) {
            $config = $this->questionTypes
                ->for($question->type)
                ->makeSnapshot($question);

            $questions[] = [
                'uuid' => $question->uuid,
                'type' => $question->type->value,
                'text' => $question->text,
                'position' => $question->position,
                'max_points' => $question->max_points,
                ...$config,
            ];
        }

        return [
            'quiz' => [
                'uuid' => $quiz->uuid,
                'title' => $quiz->title,
                'description' => $quiz->description,
            ],
            'questions' => $questions,
        ];
    }
}
