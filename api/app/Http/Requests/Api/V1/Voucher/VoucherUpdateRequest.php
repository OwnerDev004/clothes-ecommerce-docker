<?php

namespace App\Http\Requests\Api\V1\Voucher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VoucherUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper(trim((string) $this->input('code'))),
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $voucherId = $this->route('id');

        return [
            'code' => ['sometimes', 'string', 'max:100', Rule::unique('vouchers', 'code')->ignore($voucherId)],
            'name' => ['sometimes', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'is_signup_coupon' => ['sometimes', 'boolean'],
            'first_order_only' => ['sometimes', 'boolean'],
            'discount_type' => ['sometimes', Rule::in(['percentage', 'fixed_amount'])],
            'discount_value' => ['sometimes', 'numeric', 'min:0'],
            'minimum_order_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'max_order' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'max_uses_per_customer' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'expires_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:today'],
        ];
    }
}
