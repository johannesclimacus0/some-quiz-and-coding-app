<?php

namespace App\Http\Requests\Admin;

use App\Models\Group;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        $group = $this->route('group');

        return $group instanceof Group
            && $this->user()?->can('update', $group) === true;
    }

    public function rules(): array
    {
        $group = $this->route('group');

        return [
            'name' => ['sometimes', 'bail', 'required', 'string', 'max:255',
                Rule::unique(Group::class, 'name')->ignore($group),
            ],
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
