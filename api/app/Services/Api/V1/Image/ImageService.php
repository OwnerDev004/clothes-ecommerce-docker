<?php

namespace App\Services\Api\V1\Image;

use App\Contracts\ImageStorageInterface;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ImageService
{
    private const THUMBNAIL_FOLDER = '/clothes_ecommerce/products/thumbnail';
    private const GALLERY_FOLDER = '/clothes_ecommerce/products/gallery';

    public function __construct(private readonly ImageStorageInterface $imageStorage)
    {
    }

    public function uploadImage($file, $folder = 'clothes_ecommerce')
    {
        $upload = $this->imageStorage->upload($file, $folder, [
            'transformation' => [
                'quality' => 'auto',
                'fetch_format' => 'auto'
            ]
        ]);

        return [
            'url' => $upload->url,
            'public_id' => $upload->publicId,
        ];
    }

    public function deleteImage(string $publicId): bool
    {
        return $this->imageStorage->delete($publicId);
    }

    public function syncProductImages(
        Product $product,
        ?array $existingImages = null,
        ?array $newImages = null,
        bool $clearImages = false
    ): void {
        $hasExistingImagesPayload = $existingImages !== null;
        $hasNewImagesPayload = $newImages !== null;
        if (!$clearImages && !$hasExistingImagesPayload && !$hasNewImagesPayload) {
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

        if ($clearImages) {
            $publicIdsToDelete = $currentImages
                ->map(function ($image) {
                    return $image->cloudinary_public_id ?: $this->imageStorage->extractPublicIdFromUrl($image->image_url);
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
                    return $image->cloudinary_public_id ?: $this->imageStorage->extractPublicIdFromUrl($image->image_url);
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
        } elseif ($hasNewImagesPayload) {
            $publicIdsToDelete = $currentImages
                ->map(function ($image) {
                    return $image->cloudinary_public_id ?: $this->imageStorage->extractPublicIdFromUrl($image->image_url);
                })
                ->filter()
                ->values()
                ->all();

            $product->images()->delete();
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

}
