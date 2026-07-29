<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\SubCategory\ListSubCategoryRequest;
use App\Http\Requests\Api\V1\Admin\SubCategory\StoreSubCategoryRequest;
use App\Http\Requests\Api\V1\Admin\SubCategory\UpdateSubCategoryRequest;
use App\Models\SubCategory;
use App\Services\Api\V1\Admin\SubCategoryService;
use App\Traits\ApiResponse;

class SubCategoryController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly SubCategoryService $subCategoryService)
    {
    }

    public function index(ListSubCategoryRequest $request)
    {
        $subCategories = $this->subCategoryService->pagination($request->validated());
        $subCategories->setCollection($subCategories->getCollection()->map(fn($subCategory) => $subCategory));

        return $this->paginate($subCategories, 'Sub Categories list');
    }

    public function show(SubCategory $sub_categories)
    {
        return $this->success($this->subCategoryService->show($sub_categories), 'Sub category detail');
    }

    public function store(StoreSubCategoryRequest $request)
    {
        $subCategory = $this->subCategoryService->store($request->validated(), $request->file('image'));
        return $this->created($subCategory->load(['category:id,name,slug', 'parent:id,name,slug']), 'Sub category created');
    }

    public function update(UpdateSubCategoryRequest $request, SubCategory $sub_categories)
    {
        $subCategory = $this->subCategoryService->update($sub_categories, $request->validated(), $request->file('image'));
        return $this->success($subCategory->fresh()->load(['category:id,name,slug', 'parent:id,name,slug']), 'Sub category updated');
    }

    public function destroy(SubCategory $sub_categories)
    {
        $this->subCategoryService->destroy($sub_categories);
        return $this->success(null, 'Sub category deleted');
    }
}
