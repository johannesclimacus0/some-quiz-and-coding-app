<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $attempt = $this->attempts->first();

        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'description' => $this->description,
            'due_at' => $this->due_at?->toISOString(),
            'attempt_status' => $attempt?->status($this->resource) ?? 'not_started',
            'result' => $attempt?->submitted_at ? [
                'correct_answers' => $attempt->correct_answers,
                'total_questions' => $attempt->total_questions,
                'percentage' => $attempt->percentage(),
            ] : null,
        ];
    }
}
