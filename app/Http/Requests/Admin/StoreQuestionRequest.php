<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProgrammingLanguage;
use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('quiz') instanceof Quiz
            && $this->user()?->can('create', Question::class) === true;
    }

    public function rules(): array
    {
        $type = $this->input('type', QuestionType::SingleChoice->value);

        return [
            'text' => 'bail|required|string|max:4096',
            'position' => 'sometimes|bail|integer|between:0,65535',
            'type' => ['sometimes', 'bail', Rule::enum(QuestionType::class)],
            'programming_language' => [
                'bail',
                'nullable',
                Rule::requiredIf($type === QuestionType::Code->value),
                Rule::prohibitedIf($type !== QuestionType::Code->value),
                Rule::enum(ProgrammingLanguage::class),
            ],
            'max_points' => 'sometimes|bail|integer|between:1,65535',
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => 'Укажите текст вопроса',
            'text.string' => 'Текст вопроса должен быть строкой',
            'text.max' => 'Текст вопроса не должен превышать 4096 символов',
            'position.integer' => 'Позиция вопроса должна быть целым числом',
            'position.between' => 'Позиция вопроса должна быть от 0 до 65535',
            'max_points.integer' => 'Максимальный балл должен быть целым числом',
            'max_points.between' => 'Максимальный балл должен быть от 1 до 65535',
            'programming_language.required' => 'Выберите язык программирования',
            'programming_language.prohibited' => 'Язык можно указать только для вопроса с кодом',
        ];
    }
}
