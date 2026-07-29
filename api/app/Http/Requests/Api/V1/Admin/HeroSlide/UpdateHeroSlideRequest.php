<?php

namespace App\Http\Requests\Api\V1\Admin\HeroSlide;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHeroSlideRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $raw = $this->all();
        $merge = [];

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
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'subtitle' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'gradient' => ['sometimes', 'nullable', 'string', 'max:500'],
            'link_url' => ['sometimes', 'nullable', 'string', 'max:500'],
            'link_text' => ['sometimes', 'nullable', 'string', 'max:100'],
            'sort_order' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:9999'],
            'status' => ['sometimes', 'boolean'],
            'image' => ['sometimes', 'nullable', 'image', 'max:5120'],
            'remove_image' => ['sometimes', 'boolean'],
        ];
    }
}
