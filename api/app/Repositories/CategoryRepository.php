<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository extends BaseRepository
{
    protected Category $category_model;

    public function __construct(Category $model)
    {
        parent::__construct($model);
        $this->category_model = $model;
    }

    public function getAll(bool $withProductCount = false): Collection
    {
        return $this->category_model
            ->newQuery()
            ->select('id', 'name', 'slug', 'des', 'image_url')
            ->when($withProductCount, function ($query) {
                $query->withCount('products');
            })
            ->orderBy('name')
            ->get();
    }

    public function createCategory(array $data): Category
    {
        return $this->category_model->create($data);
    }

    public function updateCategory(Category $category, array $data): Category
    {
        $category->update($data);
        return $category;
    }

    public function deleteCategory(Category $category): bool
    {
        return (bool) $category->delete();
    }
}

