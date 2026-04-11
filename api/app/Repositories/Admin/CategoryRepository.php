<?php

namespace App\Repositories\Admin;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CategoryRepository
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Category::query()
            ->select('id', 'name', 'slug', 'des', 'status', 'image_url', 'image_public_id', 'created_at', 'updated_at');

        $search = trim((string) ($filters['search_txt'] ?? ''));
        $sortBy = trim((string) ($filters['sort_by'] ?? 'latest'));
        $status = $filters['status'] ?? null;

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%')
                    ->orWhere('des', 'like', '%' . $search . '%');

            });
        }

        if ($status !== null && $status !== '') {
            $query->where('categories.status', filter_var($status, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $status);
        }

        match ($sortBy) {
            'oldest' => $query->orderBy('categories.id'),
            'name_asc' => $query->orderBy('categories.name'),
            'name_desc' => $query->orderByDesc('categories.name'),
            default => $query->orderByDesc('categories.id'),
        };

        $perPage = (int) ($filters['per_page'] ?? 20);

        return $query->paginate($perPage);
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
