<?php

namespace App\Data\Questions;

use App\Enums\ProgrammingLanguage;

final readonly class ProgrammingLanguageDefinition
{
    public function __construct(
        public ProgrammingLanguage $language,
        public string $label,
        public string $editorId,
        public string $fileExtension,
    ) {}

    /** @return array{language: string, label: string, editor_id: string, file_extension: string} */
    public function toArray(): array
    {
        return [
            'language' => $this->language->value,
            'label' => $this->label,
            'editor_id' => $this->editorId,
            'file_extension' => $this->fileExtension,
        ];
    }
}
