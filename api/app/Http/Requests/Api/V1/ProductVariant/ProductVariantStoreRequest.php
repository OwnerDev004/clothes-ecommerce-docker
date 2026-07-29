<?php

namespace App\Http\Requests\Api\V1\ProductVariant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductVariantStoreRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (!$this->filled('color_name')) {
            $this->merge([
                'color_name' => $this->input('color_label') ?: $this->input('color') ?: 'Unknown',
            ]);
        }
    }

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            "product_id" => ["required", "integer", "exists:products,id"],
            "sku" => ["nullable", "string", "max:255", "unique:product_variants,sku"],
            "color" => [
                "nullable",
                "string",
                "max:64",
                Rule::unique("product_variants", "color")->where(function ($query) {
                    return $query
                        ->where("product_id", $this->input("product_id"))
                        ->where("size_id", $this->input("size_id"));
                }),
            ],
            "color_label" => [
                "nullable",
                "string",
                "max:64",
                Rule::unique('product_variants', "color_label")->where(function ($q) {
                    return $q->where("product_id", $this->input("product_id"))
                        ->where("size_id", $this->input('size_id'));
                })
            ],
            "color_name" => ["required", "string", "max:64"],
            "size_id" => ["nullable", "integer", "exists:sizes,id"],
            "stock_quantity" => ["nullable", "integer", "min:0"],
            "sell_price" => ["required", "numeric", "min:0"],
            "cost_price" => ["required", "numeric", "min:0"],
        ];
    }
}
