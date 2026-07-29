<?php
namespace App\Services\Api\V1\Admin;

use App\Contracts\ImageStorageInterface;
use App\Models\SubCategory;
use App\Repositories\Admin\SubCategoryRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

class SubCategoryService
{
    public function __construct(
        private readonly ImageStorageInterface $imageStorage,
        private readonly SubCategoryRepository $subCategoryRepository
    ) {

    }

    public function pagination(array $filters = []): LengthAwarePaginator
    {
        return $this->subCategoryRepository->paginate($filters);
    }

    public function store(array $payload, ?UploadedFile $image): SubCategory
    {
        $normalized = [
            'name' => trim((string) $payload['name']),
            'des' => $payload['des'] ?? null,
            'status' => array_key_exists('status', $payload) ? (bool) $payload['status'] : true,
        ];

        $parentId = $payload['parent_id'] ?? null;
        $categoryId = $payload['category_id'] ?? null;

        if (!empty($parentId)) {
            $parent = SubCategory::query()->findOrFail($parentId);
            $normalized['parent_id'] = $parent->id;
            $normalized['category_id'] = $categoryId ?: $parent->category_id;
            $normalized['level'] = 2;
        } else {
            $normalized['parent_id'] = null;
            $normalized['category_id'] = $categoryId;
            $normalized['level'] = isset($payload['level']) && (int) $payload['level'] === 2 ? 2 : 1;
        }

        if ($image) {
            $upload = $this->imageStorage->upload($image, 'clothes_ecommerce/sub-category-images');
            $normalized['image_url'] = $upload->url;
            $normalized['image_public_id'] = $upload->publicId;
        }

        return $this->subCategoryRepository->create($normalized);
    }

    public function update(SubCategory $subCategory, array $payload, ?UploadedFile $image): SubCategory
    {
        $normalized = [];

        if (array_key_exists('name', $payload)) {
            $normalized['name'] = trim((string) $payload['name']);
        }
        if (array_key_exists('des', $payload)) {
            $normalized['des'] = $payload['des'];
        }
        if (array_key_exists('status', $payload)) {
            $normalized['status'] = (bool) $payload['status'];
        }

        $parentIdProvided = array_key_exists('parent_id', $payload);
        $categoryIdProvided = array_key_exists('category_id', $payload);
        $parentId = $payload['parent_id'] ?? null;
        $existingPublicId = $subCategory->image_public_id ?: $this->imageStorage->extractPublicIdFromUrl($subCategory->image_url);

        if ($parentIdProvided) {
            if (!empty($parentId)) {
                $parent = SubCategory::query()->findOrFail($parentId);
                $normalized['parent_id'] = $parent->id;
                $normalized['level'] = 2;
                $normalized['category_id'] = $categoryIdProvided && !empty($payload['category_id'])
                    ? $payload['category_id']
                    : $parent->category_id;
            } else {
                $normalized['parent_id'] = null;
                $normalized['level'] = array_key_exists('level', $payload) && (int) $payload['level'] === 2 ? 2 : 1;
                if ($categoryIdProvided) {
                    $normalized['category_id'] = $payload['category_id'];
                }
            }
        } elseif ($categoryIdProvided) {
            $normalized['category_id'] = $payload['category_id'];
        }

        if (array_key_exists('level', $payload) && !$parentIdProvided) {
            $normalized['level'] = (int) $payload['level'] === 2 ? 2 : 1;
        }

        $shouldRemoveImage = ($payload['remove_image'] ?? false)
            || (array_key_exists('image', $payload) && $payload['image'] === null && !$image);

        if ($shouldRemoveImage) {
            $this->imageStorage->delete($existingPublicId ? (string) $existingPublicId : null);
            $normalized['image_url'] = null;
            $normalized['image_public_id'] = null;
        }

        if ($image) {
            $this->imageStorage->delete($existingPublicId ? (string) $existingPublicId : null);

            $upload = $this->imageStorage->upload($image, 'clothes_ecommerce/sub-category-images');
            $normalized['image_url'] = $upload->url;
            $normalized['image_public_id'] = $upload->publicId;
        }

        return $this->subCategoryRepository->update($subCategory, $normalized);
    }

    public function destroy(SubCategory $subCategory): void
    {
        $existingPublicId = $subCategory->image_public_id ?: $this->imageStorage->extractPublicIdFromUrl($subCategory->image_url);
        $this->imageStorage->delete($existingPublicId ? (string) $existingPublicId : null);

        $subCategory->loadMissing('children');
        foreach ($subCategory->children as $child) {
            $childPublicId = $child->image_public_id ?: $this->imageStorage->extractPublicIdFromUrl($child->image_url);
            $this->imageStorage->delete($childPublicId ? (string) $childPublicId : null);
        }

        $this->subCategoryRepository->destroy($subCategory);

    }
}
