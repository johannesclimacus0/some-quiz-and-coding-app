<?php

namespace App\Contracts;

use App\Models\Quiz;

interface QuizSnapshotFactory
{
    public function make(Quiz $quiz): array;
}
