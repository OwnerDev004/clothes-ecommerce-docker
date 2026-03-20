<?php

namespace App\Services\Api\V1\Admin;

use App\Models\Category;
use App\Repositories\Admin\CategoryRepository;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

class CategoryService
{
    public function __construct(private readonly CategoryRepository $categoryRepository)
    {
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
        ];

        if ($image) {
            $upload = Cloudinary::uploadApi()->upload(
                $image->getRealPath(),
                ['folder' => 'clothes_ecommerce/category-images']
            );

            $payload['image_url'] = $upload['secure_url'] ?? null;
            $payload['image_public_id'] = $upload['public_id'] ?? null;
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

        $shouldRemoveImage = ($validated['remove_image'] ?? false)
            || (array_key_exists('image', $validated) && $validated['image'] === null && !$image);

        $existingPublicId = $category->image_public_id ?: $this->extractPublicIdFromUrl($category->image_url);

        if ($shouldRemoveImage) {
            if ($existingPublicId) {
                $this->deleteImageByPublicId((string) $existingPublicId);
            }
            $payload['image_url'] = null;
            $payload['image_public_id'] = null;
        }

        if ($image) {
            if ($existingPublicId) {
                $this->deleteImageByPublicId((string) $existingPublicId);
            }

            $upload = Cloudinary::uploadApi()->upload(
                $image->getRealPath(),
                ['folder' => 'clothes_ecommerce/category-images']
            );

            $payload['image_url'] = $upload['secure_url'] ?? null;
            $payload['image_public_id'] = $upload['public_id'] ?? null;
        }

        return $this->categoryRepository->update($category, $payload);
    }

    public function destroy(Category $category): void
    {
        $existingPublicId = $category->image_public_id ?: $this->extractPublicIdFromUrl($category->image_url);
        if ($existingPublicId) {
            $this->deleteImageByPublicId((string) $existingPublicId);
        }

        $this->categoryRepository->delete($category);
    }

    private function deleteImageByPublicId(string $publicId): void
    {
        try {
            Cloudinary::uploadApi()->destroy($publicId, [
                'resource_type' => 'image',
                'type' => 'upload',
            ]);
        } catch (\Throwable $e) {
            // Best effort only; database update/delete should still proceed.
        }
    }

    private function extractPublicIdFromUrl(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path) || strpos($path, '/upload/') === false) {
            return null;
        }

        $afterUpload = substr($path, strpos($path, '/upload/') + 8);
        $parts = array_values(array_filter(explode('/', ltrim($afterUpload, '/'))));
        if (empty($parts)) {
            return null;
        }

        foreach ($parts as $index => $part) {
            if (preg_match('/^v\d+$/', $part)) {
                $parts = array_slice($parts, $index + 1);
                break;
            }
        }

        if (empty($parts)) {
            return null;
        }

        $publicIdWithExtension = implode('/', $parts);
        $publicId = preg_replace('/\.[^.\/]+$/', '', $publicIdWithExtension);

        return is_string($publicId) && $publicId !== '' ? urldecode($publicId) : null;
    }
}
