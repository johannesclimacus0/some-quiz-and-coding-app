<?php

namespace App\Http\Requests\Admin;

use App\Models\Group;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Group::class) === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['bail', 'required', 'string', 'max:255', Rule::unique(Group::class, 'name')],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Укажите название группы',
            'name.string' => 'Название группы должно быть строкой',
            'name.max' => 'Название группы не должно превышать 255 символов',
            'name.unique' => 'Группа с таким названием уже существует',
        ];
    }
}
