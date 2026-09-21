<?php

namespace App\Services\ProgrammingLanguages;

use App\Data\Questions\ProgrammingLanguageDefinition;
use App\Enums\ProgrammingLanguage;

final class JavaLanguageFactory extends ProgrammingLanguageFactory
{
    public function type(): ProgrammingLanguage
    {
        return ProgrammingLanguage::Java;
    }

    protected function createLanguage(): ProgrammingLanguageDefinition
    {
        return new ProgrammingLanguageDefinition(
            language: $this->type(),
            label: 'Java',
            editorId: 'java',
            fileExtension: 'java',
        );
    }
}
