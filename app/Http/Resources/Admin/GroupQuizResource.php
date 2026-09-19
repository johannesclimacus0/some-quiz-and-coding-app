<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupQuizResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'due_at' => $this->due_at?->toISOString(),
            'assigned' => (bool) $this->assigned,
        ];
    }
}
