<?php

namespace App\Contracts;

interface QuizParser
{
    public function isFormatSupported(string $format): bool;

    public function parse(string $contents): iterable;
}
