<?php

namespace App\Services\Imports;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class QuizImportValidator
{
    public function validate(array $quiz): array
    {
        $validator = Validator::make($quiz, [
            'title' => 'bail|required|string|max:255',
            'description' => 'bail|nullable|string|max:4096',
            'due_at' => ['bail', 'nullable', 'date', Rule::date()->after(now())],
            'questions' => 'bail|required|array|min:1|max:200',
            'questions.*' => 'required|array',
            'questions.*.text' => 'bail|required|string|max:4096',
            'questions.*.position' => 'bail|sometimes|integer|between:0,65535',
            'questions.*.answers' => 'bail|required|array|min:2|max:20',
            'questions.*.answers.*' => 'required|array',
            'questions.*.answers.*.text' => 'bail|required|string|max:2048',
            'questions.*.answers.*.position' => 'bail|sometimes|integer|between:0,65535',
            'questions.*.answers.*.is_correct' => 'bail|required|boolean',
        ], [
            'due_at.after' => 'Время не может быть раньше чем сейчас',
        ]);

        $validator->after(function ($validator) use ($quiz): void {
            $questions = $quiz['questions'] ?? null;

            if (!is_array($questions)) {
                return;
            }

            foreach ($questions as $questionIndex => $question) {
                if (!is_array($question)) {
                    continue;
                }

                $answers = $question['answers'] ?? null;

                if (!is_array($answers)) {
                    continue;
                }

                $correctAnswersCount = count(array_filter($answers,
                    fn ($answer): bool => is_array($answer)
                        && ($answer['is_correct'] ?? false),
                ));

                if ($correctAnswersCount !== 1) {
                    $validator->errors()->add(
                        'questions.' . $questionIndex . '.answers',
                        'У вопроса может быть только 1 правильный ответ'
                    );
                }
            }
        });

        return $validator->validate();
    }
}
