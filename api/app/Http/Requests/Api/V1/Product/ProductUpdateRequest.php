<?php

namespace App\Http\Requests\Api\V1\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $raw = $this->all();
        $merge = [];

        if (array_key_exists('new_images', $raw)) {
            $merge['new_images'] = is_array($raw['new_images']) ? $raw['new_images'] : [];
        }

        if (array_key_exists('existing_images', $raw)) {
            $merge['existing_images'] = is_array($raw['existing_images']) ? $raw['existing_images'] : [];
        }

        // If client explicitly sends image keys but both are empty/null, treat as clear-all.
        $hasNewImagesKey = array_key_exists('new_images', $raw);
        $hasExistingImagesKey = array_key_exists('existing_images', $raw);
        $normalizedNewImages = $merge['new_images'] ?? null;
        $normalizedExistingImages = $merge['existing_images'] ?? null;
        $bothEmpty = $hasNewImagesKey
            && empty($normalizedNewImages)
            && (!$hasExistingImagesKey || empty($normalizedExistingImages));

        if ($bothEmpty && !array_key_exists('clear_images', $raw)) {
            $merge['clear_images'] = true;
        }

        if (!empty($merge)) {
            $this->merge($merge);
        }
    }

    public function authorize()
    {
        return true;
        // return auth()->guard('admin')->check();

    }

    public function rules()
    {
        $productId = $this->route('id');
        return [
            "name" => [
                "sometimes",
                "string",
                "max:255",
                Rule::unique('products', 'name')->ignore($productId),
            ],
            "desc" => "sometimes|nullable|string",
            "price" => "sometimes|numeric|min:0",
            "category_id" => "sometimes|integer|exists:categories,id",
            "dress_type_id" => "sometimes|integer|exists:dress_types,id",
            "clear_images" => "nullable|boolean",

            // Mixed update payload: keep existing + upload new.
            "existing_images" => "nullable|array",
            "existing_images.*.id" => [
                "required",
                "integer",
                Rule::exists('product_images', 'id')->where(function ($query) use ($productId) {
                    $query->where('product_id', $productId);
                }),
            ],
            "existing_images.*.image_type" => "nullable|in:thumbnail,gallery",
            "existing_images.*.sort_order" => "nullable|integer|min:0",

            "new_images" => "nullable|array",
            "new_images.*.file" => "nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120",
            "new_images.*.image_type" => "nullable|in:thumbnail,gallery",
            "new_images.*.sort_order" => "nullable|integer|min:0",
        ];
    }







}
