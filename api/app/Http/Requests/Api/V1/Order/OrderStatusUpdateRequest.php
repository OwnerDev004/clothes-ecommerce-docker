<?php

namespace App\Http\Requests\Api\V1\Order;

use Illuminate\Foundation\Http\FormRequest;

class OrderStatusUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'required', 'string', 'in:order_confirming,payment_confirmed,processing,shipped,delivered,cancelled,refunded'],
            'order_note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
