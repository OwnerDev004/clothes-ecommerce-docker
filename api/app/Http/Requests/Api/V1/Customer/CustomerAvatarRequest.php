<?php

namespace App\Http\Requests\Api\V1\Customer; // Changed from Api\V1\Customer

use Illuminate\Foundation\Http\FormRequest;

class CustomerAvatarRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->guard('customer')->check();
    }

    public function rules()
    {
        return [
            "avatar" => "required|image|mimes:jpeg,png,jpg,gif,webp|max:5120"
        ];
    }
}