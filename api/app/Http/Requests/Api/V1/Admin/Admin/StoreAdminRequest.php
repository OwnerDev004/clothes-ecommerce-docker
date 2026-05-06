<?php

namespace App\Http\Requests\Api\V1\Admin\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:male,female'],
            'dob' => ['nullable', 'date'],
            'user_name' => ['required', 'string', 'max:255', 'unique:users,user_name'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'string', Rule::exists('roles', 'slug')->where('status', true)],
        ];
    }
}
