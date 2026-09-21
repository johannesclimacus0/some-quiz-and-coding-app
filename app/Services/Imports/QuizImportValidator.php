<?php

namespace App\Services\Imports;

use App\Enums\ProgrammingLanguage;
use App\Enums\QuestionType;
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
            'questions.*.type' => ['bail', 'sometimes', Rule::enum(QuestionType::class)],
            'questions.*.programming_language' => ['bail', 'sometimes', 'nullable', Rule::enum(ProgrammingLanguage::class)],
            'questions.*.max_points' => 'bail|sometimes|integer|between:1,65535',
            'questions.*.answers' => 'bail|sometimes|nullable|array|max:20',
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

                $answers = is_array($question['answers'] ?? null) ? $question['answers'] : [];
                $type = is_string($question['type'] ?? null)
                    ? QuestionType::tryFrom($question['type'])
                    : ($answers === [] ? QuestionType::Text : QuestionType::SingleChoice);

                if ($type === null) {
                    continue;
                }

                if ($type !== QuestionType::SingleChoice && $answers !== []) {
                    $validator->errors()->add(
                        'questions.' . $questionIndex . '.answers',
                        'Варианты ответа допустимы только для вопроса с одним выбором'
                    );

                    continue;
                }

                if ($type === QuestionType::SingleChoice && count($answers) < 2) {
                    $validator->errors()->add(
                        'questions.' . $questionIndex . '.answers',
                        'У вопроса должно быть минимум 2 варианта ответа'
                    );

                    continue;
                }

                if ($type === QuestionType::Code && empty($question['programming_language'])) {
                    $validator->errors()->add(
                        'questions.' . $questionIndex . '.programming_language',
                        'Для вопроса с кодом требуется язык программирования'
                    );
                }

                if ($type !== QuestionType::Code && isset($question['programming_language'])) {
                    $validator->errors()->add(
                        'questions.' . $questionIndex . '.programming_language',
                        'Язык можно указать только для вопроса с кодом'
                    );
                }

                if ($type !== QuestionType::SingleChoice) {
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
