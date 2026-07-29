<?php

namespace App\Services\Api\V1\Admin;

use App\Contracts\ImageStorageInterface;
use App\Models\HeroSlide;
use App\Repositories\Admin\HeroSlideRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

class HeroSlideService
{
    public function __construct(
        private readonly HeroSlideRepository $heroSlideRepository,
        private readonly ImageStorageInterface $imageStorage,
    ) {
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->heroSlideRepository->paginate($filters);
    }

    public function store(array $validated, ?UploadedFile $image): HeroSlide
    {
        $payload = [
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'description' => $validated['description'] ?? null,
            'gradient' => $validated['gradient'] ?? null,
            'link_url' => $validated['link_url'] ?? null,
            'link_text' => $validated['link_text'] ?? 'Shop Now',
            'sort_order' => $validated['sort_order'] ?? 0,
            'status' => $validated['status'] ?? true,
        ];

        if ($image) {
            $upload = $this->imageStorage->upload($image, 'clothes_ecommerce/hero-slides');
            $payload['image_url'] = $upload->url;
            $payload['image_public_id'] = $upload->publicId;
        }

        return $this->heroSlideRepository->create($payload);
    }

    public function update(HeroSlide $heroSlide, array $validated, ?UploadedFile $image): HeroSlide
    {
        $payload = [];
        if (array_key_exists('title', $validated)) {
            $payload['title'] = $validated['title'];
        }
        if (array_key_exists('subtitle', $validated)) {
            $payload['subtitle'] = $validated['subtitle'];
        }
        if (array_key_exists('description', $validated)) {
            $payload['description'] = $validated['description'];
        }
        if (array_key_exists('gradient', $validated)) {
            $payload['gradient'] = $validated['gradient'];
        }
        if (array_key_exists('link_url', $validated)) {
            $payload['link_url'] = $validated['link_url'];
        }
        if (array_key_exists('link_text', $validated)) {
            $payload['link_text'] = $validated['link_text'];
        }
        if (array_key_exists('sort_order', $validated)) {
            $payload['sort_order'] = $validated['sort_order'];
        }
        if (array_key_exists('status', $validated)) {
            $payload['status'] = $validated['status'];
        }

        $shouldRemoveImage = ($validated['remove_image'] ?? false)
            || (array_key_exists('image', $validated) && $validated['image'] === null && !$image);

        $existingPublicId = $heroSlide->image_public_id ?: $this->imageStorage->extractPublicIdFromUrl($heroSlide->image_url);

        if ($shouldRemoveImage) {
            $this->imageStorage->delete($existingPublicId ? (string) $existingPublicId : null);
            $payload['image_url'] = null;
            $payload['image_public_id'] = null;
        }

        if ($image) {
            $this->imageStorage->delete($existingPublicId ? (string) $existingPublicId : null);

            $upload = $this->imageStorage->upload($image, 'clothes_ecommerce/hero-slides');
            $payload['image_url'] = $upload->url;
            $payload['image_public_id'] = $upload->publicId;
        }

        return $this->heroSlideRepository->update($heroSlide, $payload);
    }

    public function destroy(HeroSlide $heroSlide): void
    {
        $existingPublicId = $heroSlide->image_public_id ?: $this->imageStorage->extractPublicIdFromUrl($heroSlide->image_url);
        $this->imageStorage->delete($existingPublicId ? (string) $existingPublicId : null);

        $this->heroSlideRepository->delete($heroSlide);
    }
}
