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
        $searchText = trim((string) $request->query('search_txt', $request->query('searchTxt', '')));
        $category = $request->query('category');
        $subCategory = $request->query('sub_category');
        $collection = $request->query('collection', $request->query('dress_style'));
        $price = $request->query('price');
        $priceMin = $request->query('price_min');
        $priceMax = $request->query('price_max');

        $applyProductFilters = function ($query, array $options = []) use ($searchText, $category, $subCategory, $collection, $price, $priceMin, $priceMax) {
            $ignoreSubCategory = $options['ignore_sub_category'] ?? false;
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
                            ->where('c.slug', $category);
                    });
                }
            }

            if (!is_null($collection) && $collection !== '') {
                if (is_numeric($collection)) {
                    $query->whereExists(function ($sub) use ($collection) {
                        $sub->selectRaw('1')
                            ->from('collection_product as cp')
                            ->whereColumn('cp.product_id', 'p.id')
                            ->where('cp.collection_id', (int) $collection);
                    });
                } else {
                    $query->whereExists(function ($sub) use ($collection) {
                        $sub->selectRaw('1')
                            ->from('collection_product as cp')
                            ->join('collections as d', 'd.id', '=', 'cp.collection_id')
                            ->whereColumn('cp.product_id', 'p.id')
                            ->where(function ($w) use ($collection) {
                                $w->where('d.slug', $collection)
                                    ->orWhere('d.name', 'like', '%' . $collection . '%');
                            });
                    });
                }
            }

            if (!$ignoreSubCategory && !is_null($subCategory) && $subCategory !== '') {
                if (is_numeric($subCategory)) {
                    $query->where('p.sub_category_id', (int) $subCategory);
                } else {
                    $query->whereExists(function ($sub) use ($subCategory) {
                        $sub->selectRaw('1')
                            ->from('sub_categories as sc')
                            ->whereColumn('sc.id', 'p.sub_category_id')
                            ->where('sc.slug', $subCategory);
                    });
                }
            }

            if (!is_null($price) && $price !== '') {
                $query->where('p.price', '<=', (float) $price);
            }

            if (!is_null($priceMin) && $priceMin !== '') {
                $query->where('p.price', '>=', (float) $priceMin);
            }

            if (!is_null($priceMax) && $priceMax !== '') {
                $query->where('p.price', '<=', (float) $priceMax);
            }
        };

        $productIdsQuery = DB::table('products as p')->select('p.id');
        $applyProductFilters($productIdsQuery);

        $categories = DB::table('categories as c')
            ->select('c.id', 'c.name', 'c.slug', 'c.image_url')
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

        $collections = DB::table('collections as d')
            ->select('d.id', 'd.name', 'd.slug', 'd.sort_order', 'd.image_url')
            ->whereExists(function ($sub) {
                $sub->selectRaw('1')
                    ->from('collection_product as cp')
                    ->whereColumn('cp.collection_id', 'd.id');
            })
            ->orderBy('d.sort_order')
            ->orderBy('d.name')
            ->get();

        $subCategoryGrouped = DB::table('sub_categories as sc')
            ->join('products as p', 'p.sub_category_id', '=', 'sc.id')
            ->whereIn('p.id', $productIdsQuery)
            ->selectRaw('MAX(sc.id) as sub_category_id, sc.name')
            ->groupBy('sc.name')
            ->orderBy('sc.name');

        $subCategories = DB::table('sub_categories as sc')
            ->joinSub($subCategoryGrouped, 'grp', function ($join) {
                $join->on('grp.sub_category_id', '=', 'sc.id');
            })
            ->select('sc.id', 'sc.name', 'sc.slug', 'sc.category_id')
            ->orderBy('sc.name')
            ->get();

        $hasNullSubCategory = DB::table('products as p')
            ->whereIn('p.id', $productIdsQuery)
            ->whereNull('p.sub_category_id')
            ->exists();

        if ($hasNullSubCategory) {
            $subCategories->push((object) [
                'id' => null,
                'name' => null,
                'slug' => null,
                'category_id' => null,
            ]);
        }

        return $this->success([
            'categories' => $categories,
            'sub_categories' => $subCategories,
            'colors' => $colors,
            'sizes' => $sizes,
            'collections' => $collections,
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
