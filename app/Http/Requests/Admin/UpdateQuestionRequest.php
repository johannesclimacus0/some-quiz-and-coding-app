<?php

namespace App\Http\Requests\Admin;

use App\Models\Question;
use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $question = $this->route('question');

        return $question instanceof Question
            && $this->user()?->can('update', $question) === true;
    }

    public function rules(): array
    {
        return [
            'text' => 'sometimes|bail|required|string|max:4096',
            'position' => 'sometimes|bail|integer|between:0,65535',
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
        ];
    }
}
