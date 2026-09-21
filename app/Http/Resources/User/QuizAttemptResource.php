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
                            'type' => $question['type'],
                            'text' => $question['text'],
                            'position' => $question['position'],
                            'max_points' => $question['max_points'],
                            'public_config' => $question['public_config'],
                        ];
                    })
                    ->values()
                    ->all(),
            ],
            'responses' => (object) $this->answers->mapWithKeys(fn ($answer): array => [$answer->question_uuid => [
                ...$answer->response,
                'feedback' => $answer->grading_status->value === 'graded' ? $answer->feedback : null,
            ]])->all(),
            'result' => $this->submitted_at ? [
                'earned_points' => $this->earned_points,
                'max_points' => $this->max_points,
                'percentage' => $this->percentage(),
                'grading_status' => $this->grading_status->value,
                'graded_at' => $this->graded_at?->toISOString(),
            ] : null,
            'started_at' => $this->started_at->toISOString(),
        ];
    }
}
