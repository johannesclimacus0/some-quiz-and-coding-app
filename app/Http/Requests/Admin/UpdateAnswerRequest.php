<?php

namespace App\Http\Requests\Admin;

use App\Models\Answer;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $answer = $this->route('answer');

        return $answer instanceof Answer
            && $this->user()?->can('update', $answer) === true;
    }

    public function rules(): array
    {
        return [
            'text' => 'sometimes|bail|required|string|max:2048',
            'position' => 'sometimes|bail|integer|between:0,65535',
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => 'Укажите текст ответа',
            'text.string' => 'Текст ответа должен быть строкой',
            'text.max' => 'Текст ответа не должен превышать 2048 символов',
            'position.integer' => 'Позиция ответа должна быть целым числом',
            'position.between' => 'Позиция ответа должна быть от 0 до 65535',
        ];
    }
}
