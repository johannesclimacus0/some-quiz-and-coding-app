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
            'answer_uuid' => 'bail|required|uuid',
        ];
    }

    public function messages(): array
    {
        return [
            'answer_uuid.required' => 'Выберите ответ',
            'answer_uuid.uuid' => 'Передан некорректный идентификатор ответа',
        ];
    }
}
