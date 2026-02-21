<?php

namespace App\Http\Requests\Api\V1\Payment;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipping_province' => ['required', 'string', 'max:100'],
            'shipping_address' => ['nullable', 'string', 'max:255'],
            'shipping_phone' => ['nullable', 'string', 'max:30'],
            'payment_method' => ['nullable', 'in:aba,acelida,cash_on_delivery'],
            'voucher_code' => ['nullable', 'string', 'max:100'],
        ];
    }
}
