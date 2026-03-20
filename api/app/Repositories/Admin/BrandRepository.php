<?php

namespace App\Repositories\Admin;

use App\Models\Brand;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BrandRepository
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Brand::query()
            ->select('id', 'name', 'slug', 'sort_order', 'image_url', 'image_public_id', 'created_at', 'updated_at');

        $search = trim((string) ($filters['search_txt'] ?? ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%');
            });
        }

        $perPage = (int) ($filters['per_page'] ?? 20);

        return $query
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(array $payload): Brand
    {
        return Brand::query()->create($payload);
    }

    public function update(Brand $brand, array $payload): Brand
    {
        $brand->update($payload);
        return $brand;
    }

    public function delete(Brand $brand): void
    {
        $brand->delete();
    }
}

