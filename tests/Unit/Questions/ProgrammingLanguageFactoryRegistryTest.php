<?php

namespace Tests\Unit\Questions;

use App\Enums\ProgrammingLanguage;
use App\Services\ProgrammingLanguages\ProgrammingLanguageFactoryRegistry;
use Tests\TestCase;

class ProgrammingLanguageFactoryRegistryTest extends TestCase
{
    public function test_it_creates_definitions_for_supported_languages(): void
    {
        $registry = app(ProgrammingLanguageFactoryRegistry::class);

        $this->assertSame([
            'language' => 'cpp',
            'label' => 'C++',
            'editor_id' => 'cpp',
            'file_extension' => 'cpp',
        ], $registry->make(ProgrammingLanguage::Cpp)->toArray());
        $this->assertSame('sql', $registry->make(ProgrammingLanguage::Sql)->editorId);
        $this->assertSame('java', $registry->make(ProgrammingLanguage::Java)->fileExtension);
    }
}
