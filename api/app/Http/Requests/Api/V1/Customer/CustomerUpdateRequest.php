<?php

namespace App\Http\Requests\Api\V1\Customer;

use Illuminate\Foundation\Http\FormRequest;

class CustomerUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        $customerId = auth()->guard('customer')->id();


        return [
            'user_name' => [
                'sometimes',
                'required',
                'string',
                'min:1',
                'max:255',
                'unique:customers,user_name,' . $customerId
            ],
            'full_name' => 'sometimes|nullable|string|max:255',
            'gender' => 'sometimes|nullable|in:male,female',
            'dob' => 'sometimes|nullable|date|before:today',
            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
                'unique:customers,email,' . $customerId
            ],
            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
                'unique:customers,phone,' . $customerId
            ],
            'address' => 'sometimes|nullable|string|max:500',
            'password' => 'sometimes|nullable|string|min:8|confirmed',
            'telegram_username' => 'sometimes|nullable|string|max:255',
            'enable_telegram_alerts' => 'sometimes|nullable|boolean'
        ];
    }
}
