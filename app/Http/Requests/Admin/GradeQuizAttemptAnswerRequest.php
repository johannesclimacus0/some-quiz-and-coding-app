<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class GradeQuizAttemptAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'awarded_points' => 'bail|required|integer|min:0',
            'feedback' => 'nullable|string|max:4096',
            'expected_version' => 'bail|required|integer|min:0',
        ];
    }
}
