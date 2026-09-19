<?php

namespace App\Data\Admin\Groups;

final readonly class CreateGroupData
{
    public function __construct(
        public string $name,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(name: $data['name']);
    }

    public function toArray(): array
    {
        return ['name' => $this->name];
    }
}
