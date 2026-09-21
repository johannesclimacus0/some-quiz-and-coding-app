<?php

namespace App\Services\ProgrammingLanguages;

use App\Data\Questions\ProgrammingLanguageDefinition;
use App\Enums\ProgrammingLanguage;

final class CppLanguageFactory extends ProgrammingLanguageFactory
{
    public function type(): ProgrammingLanguage
    {
        return ProgrammingLanguage::Cpp;
    }

    protected function createLanguage(): ProgrammingLanguageDefinition
    {
        return new ProgrammingLanguageDefinition(
            language: $this->type(),
            label: 'C++',
            editorId: 'cpp',
            fileExtension: 'cpp',
        );
    }
}
