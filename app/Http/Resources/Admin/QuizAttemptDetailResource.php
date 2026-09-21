<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizAttemptDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $answers = $this->answers->keyBy('question_uuid');

        return [
            ...new QuizAttemptSummaryResource($this->resource)->toArray($request),
            'questions' => collect($this->snapshot['questions'])->map(function (array $question) use ($answers): array {
                $attemptAnswer = $answers->get($question['uuid']);

                if (in_array($question['type'], ['text', 'code'], true)) {
                    return [
                        'uuid' => $question['uuid'],
                        'type' => $question['type'],
                        'text' => $question['text'],
                        'position' => $question['position'],
                        'state' => $attemptAnswer?->grading_status?->value ?? 'empty',
                        'response' => $attemptAnswer?->response ?? [],
                        'public_config' => $question['public_config'],
                        'criteria' => $question['grading_config']['criteria'],
                        'max_points' => $question['max_points'],
                        'awarded_points' => $attemptAnswer?->awarded_points,
                        'feedback' => $attemptAnswer?->feedback,
                        'grading_version' => $attemptAnswer?->grading_version ?? 0,
                    ];
                }

                $selectedUuid = $attemptAnswer?->response['answer_uuid'] ?? null;

                return [
                    'uuid' => $question['uuid'],
                    'type' => 'single_choice',
                    'text' => $question['text'],
                    'position' => $question['position'],
                    'state' => $selectedUuid === null
                        ? 'empty'
                        : ($selectedUuid === $question['grading_config']['correct_answer_uuid'] ? 'correct' : 'incorrect'),
                    'answers' => collect($question['public_config']['answers'])->map(fn (array $answer): array => [
                        'uuid' => $answer['uuid'],
                        'text' => $answer['text'],
                        'position' => $answer['position'],
                        'is_correct' => $answer['uuid'] === $question['grading_config']['correct_answer_uuid'],
                        'is_selected' => $answer['uuid'] === $selectedUuid,
                    ])->values()->all(),
                ];
            })->values()->all(),
        ];
    }
}
