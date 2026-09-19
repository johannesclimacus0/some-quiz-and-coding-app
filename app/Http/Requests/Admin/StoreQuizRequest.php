<?php

namespace App\Http\Requests\Admin;

use App\Models\Quiz;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Quiz::class) === true;
    }

    public function rules(): array
    {
        return [
            'title' => 'bail|required|string|max:255',
            'description' => 'bail|nullable|string|max:4096',
            'due_at' => ['bail', 'nullable', 'date', Rule::date()->after(now())],
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
            'due_at.after' => 'Укажите время не раньше чем сейчас',
        ];
    }
}
