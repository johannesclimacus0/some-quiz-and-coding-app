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
                'earned_points' => $attempt->earned_points,
                'max_points' => $attempt->max_points,
                'percentage' => $attempt->percentage(),
                'grading_status' => $attempt->grading_status->value,
            ] : null,
        ];
    }
}
