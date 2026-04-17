<?php

namespace App\Http\Requests\Api\V1\Admin\Collection;

use Illuminate\Foundation\Http\FormRequest;

class StoreCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255', 'unique:collections,name'],
            'desc' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'in:draft,published'],
            'sort_order' => ['nullable', 'integer'],
            'image' => ['nullable', 'image', 'max:5120'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ];
    }
}
