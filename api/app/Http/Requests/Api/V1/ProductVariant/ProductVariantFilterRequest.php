<?php

namespace App\Http\Requests\Api\V1\ProductVariant;

use Illuminate\Foundation\Http\FormRequest;

class ProductVariantFilterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            "color" => "nullable",
            "color_label" => "nullable",
            "color_name" => "nullable",
            "size" => "nullable"
        ];
    }

}
