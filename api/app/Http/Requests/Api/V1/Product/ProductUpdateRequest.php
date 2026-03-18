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

        if (array_key_exists('collection_ids', $raw) && !is_array($raw['collection_ids'])) {
            $collectionRaw = trim((string) $raw['collection_ids']);
            if ($collectionRaw !== '' && $collectionRaw[0] === '[') {
                $decoded = json_decode($collectionRaw, true);
                if (is_array($decoded)) {
                    $merge['collection_ids'] = $decoded;
                }
            } else {
                $items = array_values(array_filter(array_map('trim', explode(',', $collectionRaw)), fn($v) => $v !== ''));
                $merge['collection_ids'] = $items;
            }
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
            "sub_category_id" => "sometimes|nullable|integer|exists:sub_categories,id",
            "brand_id" => "sometimes|nullable|integer|exists:brands,id",
            "collection_ids" => "sometimes|array",
            "collection_ids.*" => "integer|exists:collections,id",
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
