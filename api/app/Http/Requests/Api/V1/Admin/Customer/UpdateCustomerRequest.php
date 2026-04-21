<?php

namespace App\Http\Requests\Api\V1\Admin\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $booleanFields = ['enable_telegram_alerts', 'status', 'remove_image'];
        $normalized = [];

        foreach ($booleanFields as $field) {
            if ($this->has($field) && $this->input($field) !== '') {
                $normalized[$field] = $this->normalizeBoolean($this->input($field));
            }
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }

    private function normalizeBoolean(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    public function rules(): array
    {
        $customer = $this->route('customer');
        $customerId = is_object($customer) ? $customer->id : $customer;

        return [
            'full_name' => ['sometimes', 'nullable', 'string', 'min:5', 'max:100'],
            'gender' => ['sometimes', 'nullable', Rule::in(['male', 'female'])],
            'dob' => ['sometimes', 'nullable', 'date'],
            'user_name' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('customers', 'user_name')->ignore($customerId)],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($customerId)],
            'phone' => ['sometimes', 'required', 'string', 'max:20', Rule::unique('customers', 'phone')->ignore($customerId)],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'profile' => ['sometimes', 'nullable', 'image', 'max:5120'],
            'telegram_username' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('customers', 'telegram_username')->ignore($customerId)],
            'enable_telegram_alerts' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'boolean'],
            'remove_image' => ['sometimes', 'boolean'],
        ];
    }
}
