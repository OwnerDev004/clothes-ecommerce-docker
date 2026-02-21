<?php

namespace App\Http\Requests\Api\V1\Payment;

use Illuminate\Foundation\Http\FormRequest;

class CreatePaymentIntentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $providers = array_keys((array) config('payment.providers', []));

        if ($providers === []) {
            $providers = ['mockpay'];
        }

        return [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'provider' => ['required', 'string', 'in:' . implode(',', $providers)],
            'currency' => ['nullable', 'string', 'size:3'],
        ];
    }
}
