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
            'response' => 'bail|required|array',
        ];
    }

    public function messages(): array
    {
        return [
            'response.required' => 'Укажите ответ',
            'response.array' => 'Передана некорректная структура ответа',
        ];
    }
}
