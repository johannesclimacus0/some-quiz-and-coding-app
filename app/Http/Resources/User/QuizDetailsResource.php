<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizDetailsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'description' => $this->description,
            'due_at' => $this->due_at?->toISOString(),
            'questions_count' => $this->questions_count,
            'attempt' => $this->attempt ? new QuizAttemptResource($this->attempt) : null,
        ];
    }
}
