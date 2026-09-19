<?php

namespace App\Http\Requests\Admin;

use App\Models\Quiz;
use Illuminate\Foundation\Http\FormRequest;

class UpdateQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        $quiz = $this->route('quiz');

        return $quiz instanceof Quiz
            && $this->user()?->can('update', $quiz) === true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|bail|required|string|max:255',
            'description' => 'sometimes|bail|nullable|string|max:4096',
            'due_at' => 'sometimes|bail|nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Укажите название теста',
            'title.string' => 'Название теста должно быть строкой',
            'title.max' => 'Название теста не должно превышать 255 символов',
            'description.string' => 'Описание теста должно быть строкой',
            'description.max' => 'Описание теста не должно превышать 4096 символов',
            'due_at.date' => 'Укажите корректную дату окончания теста',
        ];
    }
}
