<?php

namespace App\Http\Requests\Api\V1\Admin\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('enable_telegram_alerts') && $this->input('enable_telegram_alerts') !== '') {
            $this->merge([
                'enable_telegram_alerts' => $this->normalizeBoolean($this->input('enable_telegram_alerts')),
            ]);
        }
    }

    private function normalizeBoolean(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    public function rules()
    {
        return [
            'full_name' => ['nullable', 'string', 'min:5', 'max:100'],
            'gender' => ['sometimes', 'string', 'in:male,female'],
            'dob' => ['nullable', 'string'],
            'user_name' => ['required', 'string', 'max:100', 'unique:customers,user_name'],
            'email' => ['nullable', 'email', 'max:255', 'unique:customers,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:customers,phone'],
            'address' => ['nullable', 'string', 'max:255'],
            'profile' => ['nullable', 'image', 'max:5120'],
            'telegram_username' => ['nullable', 'string', 'max:255', 'unique:customers,telegram_username'],
            'enable_telegram_alerts' => ['sometimes', 'boolean'],

        ];
    }
}
