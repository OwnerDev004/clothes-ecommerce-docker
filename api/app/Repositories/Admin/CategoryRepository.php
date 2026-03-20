<?php

namespace App\Repositories\Admin;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CategoryRepository
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Category::query()
            ->select('id', 'name', 'slug', 'des', 'image_url', 'image_public_id', 'created_at', 'updated_at');

        $search = trim((string) ($filters['search_txt'] ?? ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%')
                    ->orWhere('des', 'like', '%' . $search . '%');
            });
        }

        $perPage = (int) ($filters['per_page'] ?? 20);

        return $query
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(array $payload): Category
    {
        return Category::query()->create($payload);
    }

    public function update(Category $category, array $payload): Category
    {
        $category->update($payload);
        return $category;
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }
}

