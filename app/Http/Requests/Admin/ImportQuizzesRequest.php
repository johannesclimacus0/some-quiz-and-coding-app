<?php

namespace App\Http\Requests\Admin;

use App\Models\Quiz;
use Illuminate\Foundation\Http\FormRequest;

class ImportQuizzesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Quiz::class) === true;
    }

    public function rules(): array
    {
        return [
            'file' => 'bail|required|file|max:2048|extensions:json,md,markdown',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Выберите файл для импорта',
            'file.file' => 'Не удалось прочитать загруженный файл',
            'file.max' => 'Размер файла не должен превышать 2 МБ',
            'file.extensions' => 'Поддерживаются только файлы JSON и Markdown',
        ];
    }
}
