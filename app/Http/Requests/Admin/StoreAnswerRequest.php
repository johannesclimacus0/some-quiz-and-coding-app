<?php

namespace App\Http\Requests\Admin;

use App\Models\Answer;
use App\Models\Question;
use Illuminate\Foundation\Http\FormRequest;

class StoreAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('question') instanceof Question
            && $this->user()?->can('create', Answer::class) === true;
    }

    public function rules(): array
    {
        return [
            'text' => 'bail|required|string|max:2048',
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
