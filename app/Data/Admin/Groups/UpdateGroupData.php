<?php

namespace App\Data\Admin\Groups;

use Illuminate\Support\Arr;

final readonly class UpdateGroupData
{
    private function __construct(
        public ?string $name,
        private array $fields,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            fields: array_keys($data),
        );
    }

    public function toArray(): array
    {
        return Arr::only(['name' => $this->name], $this->fields);
    }
}
