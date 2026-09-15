<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

trait HasUuidRouteKey
{
    use HasUuids;

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
