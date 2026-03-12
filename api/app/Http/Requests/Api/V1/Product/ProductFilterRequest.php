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
            "sub_category" => "nullable|string",
            "price" => "nullable|numeric",
            "price_min" => "nullable|numeric",
            "price_max" => "nullable|numeric",
            "color" => "nullable|string",
            "size" => "nullable|string",
            "dress_style" => "nullable|string",
            "page" => "nullable|integer|min:1",
            "per_page" => "nullable|integer|min:1|max:50",
        ];
    }

}
