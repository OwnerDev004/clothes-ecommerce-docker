<?php

namespace App\Http\Requests\Api\V1\ProductVariant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductVariantStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            "product_id" => ["required", "integer", "exists:products,id"],
            "color_id" => [
                "required",
                "integer",
                "exists:colors,id",
                Rule::unique("product_variants", "color_id")->where(function ($query) {
                    return $query
                        ->where("product_id", $this->input("product_id"))
                        ->where("size_id", $this->input("size_id"));
                }),
            ],
            "size_id" => ["required", "integer", "exists:sizes,id"],
            "stock_quantity" => ["nullable", "integer", "min:0"],
            "sell_price" => ["required", "numeric", "min:0"],
            "cost_price" => ["required", "numeric", "min:0"],
        ];
    }
}
