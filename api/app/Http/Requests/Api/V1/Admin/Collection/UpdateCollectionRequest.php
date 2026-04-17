<?php

namespace App\Http\Requests\Api\V1\Admin\Collection;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $collection = $this->route('collection');
        $collectionId = is_object($collection) ? $collection->id : $collection;

        return [
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('collections', 'name')->ignore($collectionId)],
            'desc' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'status' => ['sometimes', 'in:draft,published'],
            'sort_order' => ['sometimes', 'integer'],
            'image' => ['sometimes', 'nullable', 'image', 'max:5120'],
            'remove_image' => ['sometimes', 'boolean'],
            'product_ids' => ['sometimes', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ];
    }
}
