<?php

namespace App\Http\Requests\Admin;

use App\Models\Group;
use Illuminate\Foundation\Http\FormRequest;

class ListGroupAssignmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $group = $this->route('group');

        return $group instanceof Group
            && $this->user()?->can('view', $group) === true;
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
        ];
    }
}
