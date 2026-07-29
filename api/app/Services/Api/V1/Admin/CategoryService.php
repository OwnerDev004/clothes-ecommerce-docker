<?php

namespace App\Services\Api\V1\Admin;

use App\Contracts\ImageStorageInterface;
use App\Models\Category;
use App\Repositories\Admin\CategoryRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

class CategoryService
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly ImageStorageInterface $imageStorage,
    ) {
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->categoryRepository->paginate($filters);
    }

    public function store(array $validated, ?UploadedFile $image): Category
    {
        $payload = [
            'name' => $validated['name'],
            'des' => $validated['des'] ?? null,
            'status' => $validated['status'] ?? true,
        ];

        if ($image) {
            $upload = $this->imageStorage->upload($image, 'clothes_ecommerce/category-images');
            $payload['image_url'] = $upload->url;
            $payload['image_public_id'] = $upload->publicId;
        }

        return $this->categoryRepository->create($payload);
    }

    public function update(Category $category, array $validated, ?UploadedFile $image): Category
    {
        $payload = [];
        if (array_key_exists('name', $validated)) {
            $payload['name'] = $validated['name'];
        }
        if (array_key_exists('des', $validated)) {
            $payload['des'] = $validated['des'];
        }
        if (array_key_exists('status', $validated)) {
            $payload['status'] = $validated['status'];
        }

        $shouldRemoveImage = ($validated['remove_image'] ?? false)
            || (array_key_exists('image', $validated) && $validated['image'] === null && !$image);

        $existingPublicId = $category->image_public_id ?: $this->imageStorage->extractPublicIdFromUrl($category->image_url);

        if ($shouldRemoveImage) {
            $this->imageStorage->delete($existingPublicId ? (string) $existingPublicId : null);
            $payload['image_url'] = null;
            $payload['image_public_id'] = null;
        }

        if ($image) {
            $this->imageStorage->delete($existingPublicId ? (string) $existingPublicId : null);

            $upload = $this->imageStorage->upload($image, 'clothes_ecommerce/category-images');
            $payload['image_url'] = $upload->url;
            $payload['image_public_id'] = $upload->publicId;
        }

        return $this->categoryRepository->update($category, $payload);
    }

    public function destroy(Category $category): void
    {
        $existingPublicId = $category->image_public_id ?: $this->imageStorage->extractPublicIdFromUrl($category->image_url);
        $this->imageStorage->delete($existingPublicId ? (string) $existingPublicId : null);

        $this->categoryRepository->delete($category);
    }
}
