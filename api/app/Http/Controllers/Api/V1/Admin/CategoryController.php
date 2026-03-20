<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Category\ListCategoryRequest;
use App\Http\Requests\Api\V1\Admin\Category\StoreCategoryRequest;
use App\Http\Requests\Api\V1\Admin\Category\UpdateCategoryRequest;
use App\Http\Resources\Api\V1\Admin\CategoryResource;
use App\Models\Category;
use App\Services\Api\V1\Admin\CategoryService;
use App\Traits\ApiResponse;

class CategoryController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly CategoryService $categoryService)
    {
    }

    public function index(ListCategoryRequest $request)
    {
        $categories = $this->categoryService->paginate($request->validated());
        $categories->setCollection($categories->getCollection()->map(fn($category) => CategoryResource::make($category)->resolve()));

        return $this->paginate($categories, 'Categories list');
    }

    public function show(Category $category)
    {
        return $this->success(new CategoryResource($category), 'Category detail');
    }

    public function store(StoreCategoryRequest $request)
    {
        $category = $this->categoryService->store($request->validated(), $request->file('image'));

        return $this->created(new CategoryResource($category), 'Category created');
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $category = $this->categoryService->update($category, $request->validated(), $request->file('image'));

        return $this->success(new CategoryResource($category->fresh()), 'Category updated');
    }

    public function destroy(Category $category)
    {
        $this->categoryService->destroy($category);

        return $this->success(null, 'Category deleted');
    }
}
