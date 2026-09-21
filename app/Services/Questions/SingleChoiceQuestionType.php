<?php

namespace App\Services\Questions;

use App\Enums\AnswerGradingStatus;
use App\Enums\QuestionType;
use App\Exceptions\QuizAttempts\AnswerNotInQuestion;
use App\Exceptions\QuizAttempts\InvalidQuestionResponse;
use App\Models\Question;
use Illuminate\Support\Str;

final class SingleChoiceQuestionType implements QuestionTypeHandler
{
    public function type(): QuestionType
    {
        return QuestionType::SingleChoice;
    }

    public function makeSnapshot(Question $question): array
    {
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

        return [
            'public_config' => [
                'answers' => $answers,
            ],
            'grading_config' => [
                'correct_answer_uuid' => $correctAnswerUuid,
            ],
        ];
    }

    public function assertValidResponse(array $response, array $questionSnapshot): void
    {
        $answerUuid = $response['answer_uuid'] ?? null;

        if (array_keys($response) !== ['answer_uuid'] || !is_string($answerUuid) || !Str::isUuid($answerUuid)) {
            throw new InvalidQuestionResponse(
                'response.answer_uuid',
                'Invalid answer uuid',
            );
        }

        $belongsToQuestion = is_string($answerUuid)
            && collect($questionSnapshot['public_config']['answers'])->contains('uuid', $answerUuid);

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

    public function initialGrading(array $questionSnapshot, array $response): array
    {
        return [
            'grading_status' => AnswerGradingStatus::Graded,
            'awarded_points' => $response['answer_uuid'] === $questionSnapshot['grading_config']['correct_answer_uuid']
                ? $questionSnapshot['max_points']
                : 0,
        ];
    }

    public function isManual(): bool
    {
        return false;
    }
}
