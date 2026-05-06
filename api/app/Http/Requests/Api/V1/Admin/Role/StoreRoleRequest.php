<?php

namespace App\Http\Requests\Api\V1\Admin\Role;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'desc' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'boolean'],
            'is_system' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*.module_id' => ['required_with:permissions', 'integer', 'exists:modules,id'],
            'permissions.*.permissions' => ['nullable', 'array'],
            'permissions.*.permissions.*' => ['string', 'in:view,create,edit,delete'],
        ];
    }
}
