<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizAttemptDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $selected = $this->answers->pluck('answer_uuid', 'question_uuid');

        return [
            ...new QuizAttemptSummaryResource($this->resource)->toArray($request),
            'questions' => collect($this->snapshot['questions'])->map(function (array $question) use ($selected): array {
                $selectedUuid = $selected->get($question['uuid']);

                return [
                    'uuid' => $question['uuid'],
                    'text' => $question['text'],
                    'position' => $question['position'],
                    'state' => $selectedUuid === null
                        ? 'empty'
                        : ($selectedUuid === $question['correct_answer_uuid'] ? 'correct' : 'incorrect'),
                    'answers' => collect($question['answers'])->map(fn (array $answer): array => [
                        'uuid' => $answer['uuid'],
                        'text' => $answer['text'],
                        'position' => $answer['position'],
                        'is_correct' => $answer['uuid'] === $question['correct_answer_uuid'],
                        'is_selected' => $answer['uuid'] === $selectedUuid,
                    ])->values()->all(),
                ];
            })->values()->all(),
        ];
    }
}
