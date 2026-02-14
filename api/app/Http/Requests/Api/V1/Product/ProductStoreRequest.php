<?php

namespace App\Http\Requests\Api\V1\Product;

use Illuminate\Foundation\Http\FormRequest;

class ProductStoreRequest extends FormRequest
{

    public function authorize()
    {
        return true;
        // return auth()->guard('admin')->check();

    }

    public function rules()
    {
        return [
            "name" => "required|string|max:255|unique:products,name",
            "desc" => "nullable|string",
            "price" => "required|numeric|min:0",
            "category_id" => "required|integer|exists:categories,id",
            "dress_type_id" => "required|integer|exists:dress_types,id",
            "images" => "nullable|array",
            "images.*.file" => "required|image|mimes:jpeg,png,jpg,gif,webp|max:5120",
            "images.*.image_type" => "required|in:thumbnail,gallery",
            "images.*.sort_order" => "nullable|integer|min:0",

        ];
    }







}
