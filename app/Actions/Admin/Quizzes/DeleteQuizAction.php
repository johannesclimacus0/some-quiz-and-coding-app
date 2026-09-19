<?php

namespace App\Actions\Admin\Quizzes;

use App\Models\Quiz;
use Illuminate\Support\Facades\DB;

final class DeleteQuizAction
{
    public function handle(Quiz $quiz): void
    {
        DB::transaction(function () use ($quiz): void {
            $quiz->delete();
        });
    }
}
