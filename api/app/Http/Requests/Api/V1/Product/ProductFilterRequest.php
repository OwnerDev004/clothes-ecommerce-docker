<?php

namespace App\Http\Requests\Api\V1\Product;

use Illuminate\Foundation\Http\FormRequest;

class ProductFilterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            "search_txt" => "nullable|string",
            "category" => "nullable|string",
            "price" => "numeric",
            "color" => "nullable|string",
            "size" => "nullable|string",
            "dress_style" => "nullable|string"
        ];
    }

}
