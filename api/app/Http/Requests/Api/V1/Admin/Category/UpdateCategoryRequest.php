<?php

namespace App\Http\Requests\Api\V1\Admin\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $raw = $this->all();
        $merge = [];

        // Normalize remove_image to boolean when provided by multipart/form-data.
        if (array_key_exists('remove_image', $raw)) {
            $merge['remove_image'] = filter_var($raw['remove_image'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($merge['remove_image'] === null) {
                $merge['remove_image'] = false;
            }
        }

        $hasImageKey = array_key_exists('image', $raw) || $this->exists('image') || $this->files->has('image');
        $hasImageUrlKey = array_key_exists('image_url', $raw) || $this->exists('image_url');

        $imageInput = $raw['image'] ?? $this->input('image');
        $imageUrlInput = $raw['image_url'] ?? $this->input('image_url');

        $imageLooksEmpty =
            $imageInput === null
            || $imageInput === ''
            || $imageInput === 'null'
            || $imageInput === 'undefined'
            || (is_array($imageInput) && count($imageInput) === 0);

        $imageUrlLooksEmpty =
            $imageUrlInput === null
            || $imageUrlInput === ''
            || $imageUrlInput === 'null'
            || $imageUrlInput === 'undefined';

        $explicitNoFile = $hasImageKey && !$this->hasFile('image');
        $hasRemoveImageFlag = array_key_exists('remove_image', $raw) || array_key_exists('remove_image', $merge);
        $normalizedRemoveImage = (bool) ($merge['remove_image'] ?? $raw['remove_image'] ?? false);

        // Same style as ProductUpdateRequest: infer explicit clear intent when image keys are sent empty.
        if (($explicitNoFile && $imageLooksEmpty) || ($hasImageUrlKey && $imageUrlLooksEmpty)) {
            $merge['image'] = null;
            if (!$hasRemoveImageFlag) {
                $merge['remove_image'] = true;
            }
        }

        if ($normalizedRemoveImage) {
            $merge['image'] = null;
            $merge['remove_image'] = true;
        }

        if (!empty($merge)) {
            $this->merge($merge);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $category = $this->route('category');
        $categoryId = is_object($category) ? $category->id : $category;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($categoryId)],
            'des' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'status' => ['sometimes', 'boolean'],
            'image' => ['sometimes', 'nullable', 'image', 'max:5120'],
            'remove_image' => ['sometimes', 'boolean'],
        ];
    }
}
