<?php

namespace App\Http\Requests\Api\V1\ProductVariant;

use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductVariantUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (!$this->has('color_name') && $this->filled('color_label')) {
            $this->merge(['color_name' => $this->input('color_label')]);
        }
    }

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $variantId = $this->route('id');
        $currentVariant = $variantId ? ProductVariant::find($variantId) : null;

        $productId = $this->input('product_id', optional($currentVariant)->product_id);
        $sizeId = $this->input('size_id', optional($currentVariant)->size_id);

        return [
            "product_id" => ["sometimes", "required", "integer", "exists:products,id"],
            "sku" => ["sometimes", "nullable", "string", "max:255", Rule::unique("product_variants", "sku")->ignore($variantId)],
            "color" => [
                "sometimes",
                "nullable",
                "string",
                "max:64",
                Rule::unique("product_variants", "color")->where(function ($query) use ($productId, $sizeId) {
                    return $query
                        ->where("product_id", $productId)
                        ->where("size_id", $sizeId);
                })->ignore($variantId),
            ],
            "color_label" => [
                "sometimes",
                "nullable",
                "string",
                "max:64",
                Rule::unique('product_variants', "color_label")->where(function ($query) use ($productId, $sizeId) {
                    return $query
                        ->where("product_id", $productId)
                        ->where("size_id", $sizeId);
                })->ignore($variantId),
            ],
            "color_name" => ["sometimes", "required", "string", "max:64"],
            "size_id" => ["sometimes", "nullable", "integer", "exists:sizes,id"],
            "stock_quantity" => ["sometimes", "nullable", "integer", "min:0"],
            "sell_price" => ["sometimes", "required", "numeric", "min:0"],
            "cost_price" => ["sometimes", "required", "numeric", "min:0"],
        ];
    }
}
