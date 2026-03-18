<?php

namespace App\Http\Requests\Api\V1\Product;

use Illuminate\Foundation\Http\FormRequest;

class ProductStoreRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $raw = $this->input('collection_ids');
        if (is_null($raw)) {
            return;
        }

        if (is_array($raw)) {
            return;
        }

        if (is_string($raw)) {
            $trimmed = trim($raw);

            // JSON array format: "[1,2,3]"
            if ($trimmed !== '' && $trimmed[0] === '[') {
                $decoded = json_decode($trimmed, true);
                if (is_array($decoded)) {
                    $this->merge(['collection_ids' => $decoded]);
                    return;
                }
            }

            // CSV format: "1,2,3" or single id: "1"
            $items = array_values(array_filter(array_map('trim', explode(',', $trimmed)), fn($v) => $v !== ''));
            if (!empty($items)) {
                $this->merge(['collection_ids' => $items]);
            }
        }
    }

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
            "sub_category_id" => "nullable|integer|exists:sub_categories,id",
            "brand_id" => "nullable|integer|exists:brands,id",
            "collection_ids" => "required|array|min:1",
            "collection_ids.*" => "integer|exists:collections,id",
            "images" => "nullable|array",
            "images.*.file" => "required|image|mimes:jpeg,png,jpg,gif,webp|max:5120",
            "images.*.image_type" => "required|in:thumbnail,gallery",
            "images.*.sort_order" => "nullable|integer|min:0",

        ];
    }







}
