<?php

namespace App\Services\ProgrammingLanguages;

use App\Data\Questions\ProgrammingLanguageDefinition;
use App\Enums\ProgrammingLanguage;
use LogicException;

final class ProgrammingLanguageFactoryRegistry
{
    /** @var array<string, ProgrammingLanguageFactory> */
    private array $factories;

    public function __construct(
        CppLanguageFactory $cpp,
        SqlLanguageFactory $sql,
        JavaLanguageFactory $java,
    ) {
        $this->factories = [
            $cpp->type()->value => $cpp,
            $sql->type()->value => $sql,
            $java->type()->value => $java,
        ];
    }

    public function make(ProgrammingLanguage $language): ProgrammingLanguageDefinition
    {
        return ($this->factories[$language->value]
            ?? throw new LogicException('Unsupported programming language'))->make();
    }
}
