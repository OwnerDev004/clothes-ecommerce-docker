<?php

namespace App\Services\Api\V1\Admin;

use App\Models\Collection;
use App\Repositories\Admin\CollectionRepository;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

class CollectionService
{
    public function __construct(private readonly CollectionRepository $collectionRepository)
    {
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->collectionRepository->paginate($filters);
    }

    public function show(Collection $collection): Collection
    {
        return $collection->load([
            'category:id,name,slug',
            'products:id,name,slug,brand_id,category_id,sub_category_id,price',
        ]);
    }

    public function store(array $validated, ?UploadedFile $image): Collection
    {
        $payload = [
            'category_id' => (int) $validated['category_id'],
            'name' => $validated['name'],
            'desc' => $validated['desc'] ?? null,
            'status' => $validated['status'] ?? 'draft',
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ];

        if ($image) {
            $upload = Cloudinary::uploadApi()->upload(
                $image->getRealPath(),
                ['folder' => 'clothes_ecommerce/collections']
            );

            $payload['image_url'] = $upload['secure_url'] ?? null;
            $payload['image_public_id'] = $upload['public_id'] ?? null;
        }

        $collection = $this->collectionRepository->create($payload);
        if (!empty($validated['product_ids'])) {
            $collection->products()->sync($validated['product_ids']);
        }

        return $collection->load(['category:id,name,slug'])->loadCount('products');
    }

    public function update(Collection $collection, array $validated, ?UploadedFile $image): Collection
    {
        $payload = [];
        if (array_key_exists('category_id', $validated)) {
            $payload['category_id'] = (int) $validated['category_id'];
        }
        if (array_key_exists('name', $validated)) {
            $payload['name'] = $validated['name'];
        }
        if (array_key_exists('desc', $validated)) {
            $payload['desc'] = $validated['desc'];
        }
        if (array_key_exists('status', $validated)) {
            $payload['status'] = $validated['status'];
        }
        if (array_key_exists('sort_order', $validated)) {
            $payload['sort_order'] = (int) $validated['sort_order'];
        }

        if (($validated['remove_image'] ?? false) && $collection->image_public_id) {
            Cloudinary::uploadApi()->destroy($collection->image_public_id);
            $payload['image_url'] = null;
            $payload['image_public_id'] = null;
        }

        if ($image) {
            if ($collection->image_public_id) {
                Cloudinary::uploadApi()->destroy($collection->image_public_id);
            }

            $upload = Cloudinary::uploadApi()->upload(
                $image->getRealPath(),
                ['folder' => 'clothes_ecommerce/collections']
            );

            $payload['image_url'] = $upload['secure_url'] ?? null;
            $payload['image_public_id'] = $upload['public_id'] ?? null;
        }

        $this->collectionRepository->update($collection, $payload);

        if (array_key_exists('product_ids', $validated)) {
            $collection->products()->sync($validated['product_ids'] ?? []);
        }

        return $collection->fresh()->load(['category:id,name,slug'])->loadCount('products');
    }

    public function destroy(Collection $collection): void
    {
        if ($collection->image_public_id) {
            Cloudinary::uploadApi()->destroy($collection->image_public_id);
        }

        $this->collectionRepository->delete($collection);
    }
}
