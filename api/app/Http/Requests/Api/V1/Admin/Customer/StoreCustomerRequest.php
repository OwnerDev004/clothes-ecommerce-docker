<?php

namespace App\Http\Requests\Api\V1\Admin\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'full_name' => ['nullable', 'string', 'min:5', 'max:100'],
            'gender' => ['sometimes', 'string', 'in:male,female'],
            'dob' => ['nullable', 'string'],
            'user_name' => ['required', 'string', 'max:100', 'unique:customers,user_name'],
            'email' => ['required', 'email', 'max:255', 'unique:customers,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:customers,phone'],
            'address' => ['nullable', 'string', 'max:255'],
            'profile' => ['nullable', 'image', 'max:5120'],
            'telegram_username' => ['nullable', 'string', 'max:255', 'unique:customers,telegram_username'],
            'enable_telegram_alerts' => ['sometimes', 'boolean'],

        ];
    }
}