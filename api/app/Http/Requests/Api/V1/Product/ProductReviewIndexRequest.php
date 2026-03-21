<?php

namespace App\Http\Requests\Api\V1\Product;

use Illuminate\Foundation\Http\FormRequest;

class ProductReviewIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sort_by' => 'nullable|in:latest,oldest,rating_high,rating_low',
            'rating' => 'nullable|integer|min:1|max:5',
            'mine_only' => 'nullable|boolean',
        ];
    }
}
