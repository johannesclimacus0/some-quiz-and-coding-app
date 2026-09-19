<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizAttemptResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'correct_answers' => $this->correct_answers,
            'total_questions' => $this->total_questions,
            'percentage' => $this->percentage(),
            'submitted_at' => $this->submitted_at->toISOString(),
        ];
    }
}
