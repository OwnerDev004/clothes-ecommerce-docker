<?php

namespace App\Http\Requests\Api\V1\Admin\Collection;

use Illuminate\Foundation\Http\FormRequest;

class ListCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search_txt' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'status' => ['nullable', 'integer', 'in:[1, 2]'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ];
    }
}

