<?php

namespace App\Services\ProgrammingLanguages;

use App\Data\Questions\ProgrammingLanguageDefinition;
use App\Enums\ProgrammingLanguage;

abstract class ProgrammingLanguageFactory
{
    abstract public function type(): ProgrammingLanguage;

    final public function make(): ProgrammingLanguageDefinition
    {
        return $this->createLanguage();
    }

    abstract protected function createLanguage(): ProgrammingLanguageDefinition;
}
