<?php

namespace App\Http\Requests\Api\V1\Admin\Setting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'app_name' => ['required', 'string', 'max:255'],
            'app_tagline' => ['nullable', 'string', 'max:255'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:50'],
            'business_address' => ['nullable', 'string'],
            'default_currency_code' => ['required', 'string', 'max:10'],
            'exchange_rate' => ['required', 'numeric', 'min:0'],
            'shipping_fee' => ['required', 'numeric', 'min:0'],
            'free_shipping_threshold' => ['required', 'numeric', 'min:0'],
            'low_stock_threshold' => ['required', 'integer', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0'],
            'shipping_rates' => ['nullable', 'array'],
            'shipping_rates.*.province' => ['required_with:shipping_rates', 'string', 'max:255'],
            'shipping_rates.*.fee' => ['required_with:shipping_rates', 'numeric', 'min:0'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $shippingRates = $this->shipping_rates;

            if (empty($shippingRates)) {
                return;
            }

            // Check for duplicate provinces within the request
            $provinces = array_map(
                fn($province) => strtolower(str_replace(' ', '', $province)),
                array_column($shippingRates, 'province')
            );

            if (count($provinces) !== count(array_unique($provinces))) {
                $validator->errors()->add(
                    'shipping_rates',
                    'Province names must be unique within shipping rates.'
                );
            }
        });
    }
}
