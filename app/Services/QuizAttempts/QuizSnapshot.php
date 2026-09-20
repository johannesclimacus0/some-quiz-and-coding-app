<?php

namespace App\Services\QuizAttempts;

use App\Contracts\QuizSnapshotFactory;
use App\Models\Quiz;

final class QuizSnapshot implements QuizSnapshotFactory
{
    public function make(Quiz $quiz): array
    {
        $questions = [];

        foreach ($quiz->questions as $question) {
            $correctAnswerUuid = null;
            $answers = [];

            foreach ($question->answers as $answer) {
                if ($answer->is_correct) {
                    $correctAnswerUuid = $answer->uuid;
                }
                $answers[] = [
                    'uuid' => $answer->uuid,
                    'text' => $answer->text,
                    'position' => $answer->position,
                ];
            }
            $questions[] = [
                'uuid' => $question->uuid,
                'type' => $question->type->value,
                'text' => $question->text,
                'position' => $question->position,
                'max_points' => $question->max_points,
                'public_config' => [
                    'answers' => $answers,
                ],
                'grading_config' => [
                    'correct_answer_uuid' => $correctAnswerUuid,
                ],
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
