<?php

namespace App\Http\Requests\Api\V1\Admin\Customer;

use Illuminate\Foundation\Http\FormRequest;

class ListCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return
            [
                "search_txt" => ['nullable', 'string', 'max:255'],
                "sort_by" => ['nullable', 'string', 'in:latest,oldest,name_asc,name_desc'],
                "per_page" => ['nullable', 'integer', 'min;10', 'max:200']
            ];
    }
}
