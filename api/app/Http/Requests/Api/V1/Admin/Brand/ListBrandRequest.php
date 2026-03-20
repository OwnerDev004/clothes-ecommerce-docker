<?php

namespace App\Http\Requests\Api\V1\Admin\Brand;

use Illuminate\Foundation\Http\FormRequest;

class ListBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search_txt' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ];
    }
}
