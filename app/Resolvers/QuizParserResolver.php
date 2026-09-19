<?php

namespace App\Resolvers;

use App\Contracts\QuizParser;
use App\Parsers\JsonQuizParser;
use App\Parsers\MarkdownQuizParser;
use InvalidArgumentException;

final readonly class QuizParserResolver
{
    public function __construct(
        private JsonQuizParser $jsonParser,
        private MarkdownQuizParser $markdownParser,
    ) {}

    public function resolve(string $format): QuizParser
    {
        $parsers = [$this->jsonParser, $this->markdownParser];

        foreach ($parsers as $parser) {
            if ($parser->isFormatSupported($format)) {
                return $parser;
            }
        }

        throw new InvalidArgumentException("Формат $format не поддерживается");
    }
}
