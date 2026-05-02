<?php

namespace App\Http\Requests\Api\V1\Order;

use Illuminate\Foundation\Http\FormRequest;

class OrderEditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'shipping_province' => ['sometimes', 'required', 'string', 'max:100'],
            'shipping_address' => ['nullable', 'string', 'max:255'],
            'shipping_phone' => ['nullable', 'string', 'max:30'],
            'shipping_fee' => ['sometimes', 'required', 'numeric', 'min:0'],
            'order_note' => ['nullable', 'string', 'max:500'],
        ];

    }


}
