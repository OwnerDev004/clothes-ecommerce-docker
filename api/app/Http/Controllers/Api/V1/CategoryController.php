<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponse;

    protected $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $withProductCount = $request->boolean('with_product_count', false);

        $categories = $this->categoryRepository->getAll($withProductCount);

        return $this->success($categories, 'Categories list');
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        return $this->success(
            $category->only(['id', 'name', 'slug', 'des', 'image_url']),
            'Category detail'
        );
    }
}
