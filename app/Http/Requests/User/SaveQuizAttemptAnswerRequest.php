<?php

namespace App\Http\Requests\User;

use App\Models\Quiz;
use Illuminate\Foundation\Http\FormRequest;

class SaveQuizAttemptAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $quiz = $this->route('quiz');

        return $quiz instanceof Quiz
            && $this->user()?->can('participate', $quiz) === true;
    }

    public function rules(): array
    {
        return [
            'response' => 'bail|required|array:answer_uuid',
            'response.answer_uuid' => 'bail|required|uuid',
        ];
    }

    public function messages(): array
    {
        return [
            'response.required' => 'Укажите ответ',
            'response.array' => 'Передана некорректная структура ответа',
            'response.answer_uuid.required' => 'Выберите ответ',
            'response.answer_uuid.uuid' => 'Передан некорректный идентификатор ответа',
        ];
    }
}
