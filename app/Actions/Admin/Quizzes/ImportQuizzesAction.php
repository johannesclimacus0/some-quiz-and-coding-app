<?php

namespace App\Actions\Admin\Quizzes;

use App\Resolvers\QuizParserResolver;
use Illuminate\Support\Facades\DB;

final readonly class ImportQuizzesAction
{
    public function __construct(
        private QuizParserResolver $parserResolver,
        private StoreImportedQuizAction $storeImportedQuiz,
    ) {}

    public function handle(string $contents, string $format): array
    {
        $parser = $this->parserResolver->resolve($format);

        return DB::transaction(function () use ($parser, $contents): array {
            $result = [
                'quizzes' => 0,
                'questions' => 0,
                'answers' => 0,
            ];

            foreach ($parser->parse($contents) as $quizData) {
                $quiz = $this->storeImportedQuiz->handle($quizData);

                $result['quizzes']++;
                $result['questions'] += $quiz->questions->count();
                $result['answers'] += $quiz->questions->sum(
                    fn ($question): int => $question->answers->count(),
                );
            }

            return $result;
        });
    }
}
