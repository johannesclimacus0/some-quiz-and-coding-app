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
                'earned_points' => $this->earned_points,
                'max_points' => $this->max_points,
                'percentage' => $this->percentage(),
                'grading_status' => $this->grading_status->value,
            ] : null,
        ];
    }
}
