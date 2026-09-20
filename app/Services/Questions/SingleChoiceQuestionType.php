<?php

namespace App\Services\Questions;

use App\Enums\QuestionType;
use App\Exceptions\QuizAttempts\AnswerNotInQuestion;
use App\Models\Question;

class SingleChoiceQuestionType implements QuestionTypeHandler
{
    public function type(): QuestionType
    {
        return QuestionType::SingleChoice;
    }

    public function makeSnapshot(Question $question): array
    {
        $correctAnswersUuid = null;
        $answers = [];

        foreach ($question->answers as $answer) {
            if ($answer->is_correct) {
                $correctAnswersUuid = $answer->uuid;
            }
            $answers[] = [
                'uuid' => $answer->uuid,
                'text' => $answer->text,
                'position' => $answer->position,
            ];
        }

        return [
            'public_config' => [
                'answers' => $answers,
            ],
            'grading_config' => [
                'correct_answer_uuid' => $correctAnswersUuid,
            ],
        ];
    }

    public function assertValidResponse(array $response, array $questionSnapshot): void
    {
        $answerUuid = $response['answer_uuid'] ?? null;

        $belongsToQuestion = is_string($answerUuid)
            && collect($questionSnapshot['public_config']['answers'])
                ->contains('uuid', $answerUuid);

        if (!$belongsToQuestion) {
            throw new AnswerNotInQuestion;
        }
    }

    public function isComplete(array $response): bool
    {
        return isset($response['answer_uuid'])
            && is_string($response['answer_uuid'])
            && $response['answer_uuid'] !== '';
    }
}
