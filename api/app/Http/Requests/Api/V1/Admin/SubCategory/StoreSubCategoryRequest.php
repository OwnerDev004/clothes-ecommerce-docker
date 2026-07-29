<?php

namespace App\Http\Requests\Api\V1\Admin\SubCategory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:sub_categories,name'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'des' => ['nullable', 'string', 'max:1000'],
            'status' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'max:5120'],
            'level' => ['nullable', Rule::in([1, 2])],
            'parent_id' => ['nullable', 'integer', 'exists:sub_categories,id'],
        ];
    }
}
