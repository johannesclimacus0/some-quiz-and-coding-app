<?php

namespace App\Http\Requests\Admin;

use App\Models\Answer;
use App\Models\Question;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SetCorrectAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $question = $this->route('question');

        return $question instanceof Question
            && $this->user()?->can('setCorrectAnswer', $question) === true;
    }

    public function rules(): array
    {
        $question = $this->route('question');

        return [
            'answer_uuid' => ['bail', 'required', 'uuid',
                Rule::exists(Answer::class, 'uuid')
                    ->where('question_id', $question instanceof Question ? $question->getKey() : 0)
                    ->withoutTrashed(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'answer_uuid.required' => 'Выберите правильный ответ',
            'answer_uuid.uuid' => 'Передан некорректный идентификатор ответа',
            'answer_uuid.exists' => 'Выбранный ответ не принадлежит этому вопросу или был удалён',
        ];
    }
}
