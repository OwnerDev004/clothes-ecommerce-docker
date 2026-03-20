<?php

namespace App\Repositories\Admin;

use App\Models\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CollectionRepository
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Collection::query()
            ->with(['category:id,name,slug'])
            ->withCount('products')
            ->select('id', 'category_id', 'name', 'slug', 'desc', 'sort_order', 'image_url', 'image_public_id', 'created_at', 'updated_at');

        $search = trim((string) ($filters['search_txt'] ?? ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%')
                    ->orWhere('desc', 'like', '%' . $search . '%');
            });
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        $perPage = (int) ($filters['per_page'] ?? 20);

        return $query
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(array $payload): Collection
    {
        return Collection::query()->create($payload);
    }

    public function update(Collection $collection, array $payload): Collection
    {
        $collection->update($payload);
        return $collection;
    }

    public function delete(Collection $collection): void
    {
        $collection->delete();
    }
}

