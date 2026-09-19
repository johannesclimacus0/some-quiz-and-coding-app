<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'status' => $this->status(),
            'snapshot' => [
                'quiz' => [
                    'uuid' => $this->snapshot['quiz']['uuid'],
                    'title' => $this->snapshot['quiz']['title'],
                    'description' => $this->snapshot['quiz']['description'],
                ],
                'questions' => collect($this->snapshot['questions'])
                    ->map(function (array $question): array {
                        return [
                            'uuid' => $question['uuid'],
                            'text' => $question['text'],
                            'position' => $question['position'],
                            'answers' => collect($question['answers'])
                                ->map(fn (array $answer): array => [
                                    'uuid' => $answer['uuid'],
                                    'text' => $answer['text'],
                                    'position' => $answer['position'],
                                ])
                                ->values()
                                ->all(),
                        ];
                    })
                    ->values()
                    ->all(),
            ],
            'selected_answers' => $this->answers->pluck('answer_uuid', 'question_uuid')->all(),
            'result' => $this->submitted_at ? [
                'correct_answers' => $this->correct_answers,
                'total_questions' => $this->total_questions,
                'percentage' => $this->percentage(),
                'submitted_at' => $this->submitted_at->toISOString(),
            ] : null,
            'started_at' => $this->started_at->toISOString(),
        ];
    }
}
