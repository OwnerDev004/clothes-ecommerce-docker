<?php

namespace App\Http\Requests\Api\V1\Admin\SubCategory;

use Illuminate\Foundation\Http\FormRequest;

class ListSubCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search_txt' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'boolean'],
            'sort_by' => ['nullable', 'string', 'in:latest,oldest,name_asc,name_desc'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ];
    }
}
