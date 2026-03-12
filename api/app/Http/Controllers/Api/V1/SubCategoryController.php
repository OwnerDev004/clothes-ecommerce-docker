<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SubCategory;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class SubCategoryController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $category = $request->query('category');

        $subCategories = SubCategory::query()
            ->select('id', 'category_id', 'name', 'slug', 'des')
            ->when($category, function ($query) use ($category) {
                if (is_numeric($category)) {
                    $query->where('category_id', (int) $category);
                } else {
                    $query->whereHas('category', function ($q) use ($category) {
                        $q->where('slug', $category);
                    });
                }
            })
            ->orderBy('name')
            ->get();

        return $this->success($subCategories, 'Sub categories list');
    }
}

