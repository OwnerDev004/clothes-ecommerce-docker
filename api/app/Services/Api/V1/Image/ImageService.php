<?php

namespace App\Services\Api\V1\Image;

use App\Models\Product;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\DB;

class ImageService
{
    private const THUMBNAIL_FOLDER = '/clothes_ecommerce/products/thumbnail';
    private const GALLERY_FOLDER = '/clothes_ecommerce/products/gallery';

    public function uploadImage($file, $folder = 'clothes_ecommerce')
    {
        $upload = Cloudinary::uploadApi()->upload($file->getRealPath(), [
            'folder' => $folder,
            'transformation' => [
                'quality' => 'auto',
                'fetch_format' => 'auto'
            ]
        ]);

        return [
            'url' => $upload['secure_url'] ?? null,
            'public_id' => $upload['public_id'] ?? null,
        ];
    }

    public function deleteImage(string $publicId): bool
    {
        try {
            $result = Cloudinary::uploadApi()->destroy($publicId, [
                'resource_type' => 'image',
                'type' => 'upload',
            ]);

            return in_array($result['result'] ?? null, ['ok', 'not found'], true);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function syncProductImages(
        Product $product,
        ?array $existingImages = null,
        ?array $newImages = null,
        bool $clearImages = false
    ): void {
        $hasExistingImagesPayload = $existingImages !== null;
        $hasNewImagesPayload = $newImages !== null;
        $shouldSync = $clearImages || $hasExistingImagesPayload || $hasNewImagesPayload;
        if (!$shouldSync) {
            return;
        }

        $existingImages = $existingImages ?? [];
        $newImages = $newImages ?? [];
        $validNewImages = collect($newImages)
            ->filter(function ($image) {
                return isset($image['file'], $image['image_type']);
            })
            ->values()
            ->all();

        $currentImages = $product->images()->get(['id', 'image_url', 'cloudinary_public_id']);
        $publicIdsToDelete = [];
        // If client explicitly sends new_images but no valid files, treat as replace-with-empty.
        $replaceAllImages = $clearImages || !empty($validNewImages) || ($hasNewImagesPayload && empty($validNewImages));

        if ($replaceAllImages) {
            $publicIdsToDelete = $currentImages
                ->map(function ($image) {
                    return $image->cloudinary_public_id ?: $this->extractPublicIdFromUrl($image->image_url);
                })
                ->filter()
                ->values()
                ->all();

            $product->images()->delete();
        } elseif ($hasExistingImagesPayload) {
            $keepIds = collect($existingImages)
                ->pluck('id')
                ->filter()
                ->map(fn($value) => (int) $value)
                ->unique()
                ->values()
                ->all();

            $imagesToDelete = $currentImages->filter(function ($image) use ($keepIds) {
                return !in_array((int) $image->id, $keepIds, true);
            });

            $publicIdsToDelete = $imagesToDelete
                ->map(function ($image) {
                    return $image->cloudinary_public_id ?: $this->extractPublicIdFromUrl($image->image_url);
                })
                ->filter()
                ->values()
                ->all();

            $deleteQuery = $product->images();
            if (!empty($keepIds)) {
                $deleteQuery->whereNotIn('id', $keepIds);
            }
            $deleteQuery->delete();

            foreach ($existingImages as $existingImage) {
                if (!isset($existingImage['id'])) {
                    continue;
                }

                $updatePayload = [];
                if (isset($existingImage['image_type'])) {
                    $updatePayload['image_type'] = $existingImage['image_type'] === 'thumbnail' ? 'thumbnail' : 'gallery';
                }
                if (isset($existingImage['sort_order'])) {
                    $updatePayload['sort_order'] = (int) $existingImage['sort_order'];
                }

                if (!empty($updatePayload)) {
                    $product->images()
                        ->where('id', (int) $existingImage['id'])
                        ->update($updatePayload);
                }
            }
        }

        if (!empty($publicIdsToDelete)) {
            DB::afterCommit(function () use ($publicIdsToDelete) {
                foreach (array_unique($publicIdsToDelete) as $publicId) {
                    $this->deleteImage((string) $publicId);
                }
            });
        }

        foreach ($validNewImages as $image) {
            $imageType = $image['image_type'] === 'thumbnail' ? 'thumbnail' : 'gallery';
            $sortOrder = isset($image['sort_order']) ? (int) $image['sort_order'] : 0;
            $uploadedImage = $this->uploadImage($image['file'], $this->resolveFolder($imageType));

            $product->images()->create([
                'image_url' => $uploadedImage['url'] ?? null,
                'cloudinary_public_id' => $uploadedImage['public_id'] ?? null,
                'image_type' => $imageType,
                'sort_order' => $sortOrder,
            ]);
        }
    }

    private function resolveFolder(string $imageType): string
    {
        return $imageType === 'thumbnail' ? self::THUMBNAIL_FOLDER : self::GALLERY_FOLDER;
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

        $versionIndex = null;
        foreach ($parts as $index => $part) {
            if (preg_match('/^v\d+$/', $part)) {
                $versionIndex = $index;
                break;
            }
        }

        if ($versionIndex !== null) {
            $parts = array_slice($parts, $versionIndex + 1);
        }

        if (empty($parts)) {
            return null;
        }

        $publicIdWithExtension = implode('/', $parts);
        $publicId = preg_replace('/\.[^.\/]+$/', '', $publicIdWithExtension);

        return is_string($publicId) && $publicId !== '' ? urldecode($publicId) : null;
    }
}
