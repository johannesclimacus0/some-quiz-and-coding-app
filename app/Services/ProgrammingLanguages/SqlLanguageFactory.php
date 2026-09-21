<?php

namespace App\Services\ProgrammingLanguages;

use App\Data\Questions\ProgrammingLanguageDefinition;
use App\Enums\ProgrammingLanguage;

final class SqlLanguageFactory extends ProgrammingLanguageFactory
{
    public function type(): ProgrammingLanguage
    {
        return ProgrammingLanguage::Sql;
    }

    protected function createLanguage(): ProgrammingLanguageDefinition
    {
        return new ProgrammingLanguageDefinition(
            language: $this->type(),
            label: 'SQL',
            editorId: 'sql',
            fileExtension: 'sql',
        );
    }
}
