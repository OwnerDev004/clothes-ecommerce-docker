<?php

namespace App\Services\Api\V1\Admin;

use App\Contracts\ImageStorageInterface;
use App\Models\Brand;
use App\Repositories\Admin\BrandRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

class BrandService
{
    private const BRAND_IMAGE_WIDTH = 320;
    private const BRAND_IMAGE_HEIGHT = 140;

    public function __construct(
        private readonly BrandRepository $brandRepository,
        private readonly ImageStorageInterface $imageStorage,
    ) {
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->brandRepository->paginate($filters);
    }

    public function store(array $validated, ?UploadedFile $image): Brand
    {
        $payload = [
            'name' => $validated['name'],
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ];

        if ($image) {
            $upload = $this->imageStorage->upload($image, 'clothes_ecommerce/brand-images', [
                'transformation' => [
                    'width' => self::BRAND_IMAGE_WIDTH,
                    'height' => self::BRAND_IMAGE_HEIGHT,
                    'crop' => 'fill',
                    'gravity' => 'auto',
                    'background' => 'white',
                    'fetch_format' => 'auto',
                    'quality' => 'auto',
                ],
            ]);

            $payload['image_url'] = $upload->url;
            $payload['image_public_id'] = $upload->publicId;
        }

        return $this->brandRepository->create($payload);
    }

    public function update(Brand $brand, array $validated, ?UploadedFile $image): Brand
    {
        $payload = [];
        if (array_key_exists('name', $validated)) {
            $payload['name'] = $validated['name'];
        }
        if (array_key_exists('sort_order', $validated)) {
            $payload['sort_order'] = (int) $validated['sort_order'];
        }

        if (($validated['remove_image'] ?? false) && $brand->image_public_id) {
            $this->imageStorage->delete($brand->image_public_id);
            $payload['image_url'] = null;
            $payload['image_public_id'] = null;
        }

        if ($image) {
            if ($brand->image_public_id) {
                $this->imageStorage->delete($brand->image_public_id);
            }

            $upload = $this->imageStorage->upload($image, 'clothes_ecommerce/brand-images', [
                'transformation' => [
                    'width' => self::BRAND_IMAGE_WIDTH,
                    'height' => self::BRAND_IMAGE_HEIGHT,
                    'crop' => 'fill',
                    'gravity' => 'auto',
                    'background' => 'white',
                    'fetch_format' => 'auto',
                    'quality' => 'auto',
                ],
            ]);

            $payload['image_url'] = $upload->url;
            $payload['image_public_id'] = $upload->publicId;
        }

        return $this->brandRepository->update($brand, $payload);
    }

    public function destroy(Brand $brand): void
    {
        $this->imageStorage->delete($brand->image_public_id);

        $this->brandRepository->delete($brand);
    }
}
