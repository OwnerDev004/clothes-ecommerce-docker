<?php

namespace App\Http\Requests\Api\V1\Admin\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $adminId = $this->route('admin')?->id;

        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'gender' => ['sometimes', 'required', 'in:male,female'],
            'dob' => ['sometimes', 'nullable', 'date'],
            'user_name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'user_name')->ignore($adminId),
            ],
            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
                Rule::unique('users', 'phone')->ignore($adminId),
            ],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($adminId),
            ],
            'password' => ['sometimes', 'nullable', 'string', 'min:6'],
            'role' => ['sometimes', 'required', 'string', Rule::exists('roles', 'slug')->where('status', true)],
        ];
    }
}
