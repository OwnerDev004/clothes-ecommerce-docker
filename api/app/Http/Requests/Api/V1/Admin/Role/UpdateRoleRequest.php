<?php

namespace App\Http\Requests\Api\V1\Admin\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleId = $this->route('role')?->id;

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($roleId),
            ],
            'desc' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'status' => ['sometimes', 'boolean'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*.module_id' => ['required_with:permissions', 'integer', 'exists:modules,id'],
            'permissions.*.permissions' => ['nullable', 'array'],
            'permissions.*.permissions.*' => ['string', 'in:view,create,edit,delete'],
        ];
    }
}
