<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizAttemptSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'user' => [
                'uuid' => $this->user->uuid,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'status' => $this->status(),
            'started_at' => $this->started_at->toISOString(),
            'submitted_at' => $this->submitted_at?->toISOString(),
            'result' => $this->submitted_at ? [
                'correct_answers' => $this->correct_answers,
                'total_questions' => $this->total_questions,
                'percentage' => $this->percentage(),
            ] : null,
        ];
    }
}
