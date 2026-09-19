<?php

namespace App\Data\Admin\Answers;

final readonly class SetCorrectAnswerData
{
    public function __construct(
        public string $answerUuid,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            answerUuid: $data['answer_uuid'],
        );
    }
}
