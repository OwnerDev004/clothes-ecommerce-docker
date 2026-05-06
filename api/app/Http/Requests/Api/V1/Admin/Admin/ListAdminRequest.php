<?php

namespace App\Http\Requests\Api\V1\Admin\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ListAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search_txt' => ['nullable', 'string', 'max:255'],
            'sort_by' => ['nullable', 'string', 'in:latest,oldest,name_asc,name_desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ];
    }
}
