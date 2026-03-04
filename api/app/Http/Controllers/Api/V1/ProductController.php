<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Product\ProductFilterRequest;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;
class ProductController extends Controller
{
    use ApiResponse;
    protected $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(ProductFilterRequest $request)
    {
        $filters = $request->validated();

        $products = $this->productRepository->getAll($filters);
        return $this->paginate($products, 'Products list');
    }

    public function filters(Request $request)
    {
        $searchText = trim((string) $request->query('search_txt', ''));
        $category = $request->query('category');
        $dressStyle = $request->query('dress_style');
        $price = $request->query('price');

        $applyProductFilters = function ($query) use ($searchText, $category, $dressStyle, $price) {
            if ($searchText !== '') {
                $query->where(function ($q) use ($searchText) {
                    $q->where('p.name', 'like', '%' . $searchText . '%')
                        ->orWhere('p.sku', 'like', '%' . $searchText . '%')
                        ->orWhere('p.slug', 'like', '%' . $searchText . '%')
                        ->orWhere('p.desc', 'like', '%' . $searchText . '%');
                });
            }

            if (!is_null($category) && $category !== '') {
                if (is_numeric($category)) {
                    $query->where('p.category_id', (int) $category);
                } else {
                    $query->whereExists(function ($sub) use ($category) {
                        $sub->selectRaw('1')
                            ->from('categories as c')
                            ->whereColumn('c.id', 'p.category_id')
                            ->where(function ($w) use ($category) {
                                $w->where('c.slug', $category)
                                    ->orWhere('c.name', 'like', '%' . $category . '%');
                            });
                    });
                }
            }

            if (!is_null($dressStyle) && $dressStyle !== '') {
                if (is_numeric($dressStyle)) {
                    $query->where('p.dress_type_id', (int) $dressStyle);
                } else {
                    $query->whereExists(function ($sub) use ($dressStyle) {
                        $sub->selectRaw('1')
                            ->from('dress_types as d')
                            ->whereColumn('d.id', 'p.dress_type_id')
                            ->where(function ($w) use ($dressStyle) {
                                $w->where('d.slug', $dressStyle)
                                    ->orWhere('d.name', 'like', '%' . $dressStyle . '%');
                            });
                    });
                }
            }

            if (!is_null($price) && $price !== '') {
                $query->where('p.price', '<=', (float) $price);
            }
        };

        $productIdsQuery = DB::table('products as p')->select('p.id');
        $applyProductFilters($productIdsQuery);

        $categories = DB::table('categories as c')
            ->select('c.id', 'c.name', 'c.slug')
            ->whereExists(function ($sub) {
                $sub->selectRaw('1')
                    ->from('products as p')
                    ->whereColumn('p.category_id', 'c.id');
            })
            ->orderBy('name')
            ->get();

        $colors = DB::table('colors as c')
            ->select('c.id', 'c.name', 'c.hex_code')
            ->join('product_variants as pv', 'pv.color_id', '=', 'c.id')
            ->whereIn('pv.product_id', $productIdsQuery)
            ->distinct()
            ->orderBy('c.name')
            ->get();

        $sizes = DB::table('sizes as s')
            ->select('s.id', 's.name', 's.sort_order')
            ->join('product_variants as pv', 'pv.size_id', '=', 's.id')
            ->whereIn('pv.product_id', $productIdsQuery)
            ->distinct()
            ->orderBy('s.sort_order')
            ->orderBy('s.name')
            ->get();

        $dressTypes = DB::table('dress_types as d')
            ->select('d.id', 'd.name', 'd.slug', 'd.sort_order')
            ->whereExists(function ($sub) {
                $sub->selectRaw('1')
                    ->from('products as p')
                    ->whereColumn('p.dress_type_id', 'd.id');
            })
            ->orderBy('d.sort_order')
            ->orderBy('d.name')
            ->get();

        return $this->success([
            'categories' => $categories,
            'colors' => $colors,
            'sizes' => $sizes,
            'dress_types' => $dressTypes,
        ], 'Product filters');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $product = $this->productRepository->findById($id);
        if (!$product) {
            return $this->error('Product not found', 404);
        }

        return $this->success($product, 'Product detail', 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        //
    }
}
