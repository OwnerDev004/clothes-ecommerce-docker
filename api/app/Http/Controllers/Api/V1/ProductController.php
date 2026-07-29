<?php
namespace App\Http\Controllers\Api\V1;


use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Product\ProductFilterRequest;
use App\Http\Requests\Api\V1\Product\ProductReviewIndexRequest;
use App\Http\Requests\Api\V1\Product\ProductReviewStoreRequest;
use App\Models\ProductFaq;
use App\Models\Product;
use App\Models\ProductReview;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

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
    #[OA\Get(
        path: '/api/v1/products',
        tags: ['Products'],
        summary: 'Get products',
        parameters: [
            new OA\Parameter(name: 'search_txt', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'category', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sub_category', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'brand', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'price_min', in: 'query', required: false, schema: new OA\Schema(type: 'number', format: 'float')),
            new OA\Parameter(name: 'price_max', in: 'query', required: false, schema: new OA\Schema(type: 'number', format: 'float')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Products list'),
        ]
    )]
    public function index(ProductFilterRequest $request)
    {
        $filters = $request->validated();

        $products = $this->productRepository->getAll($filters);
        return $this->paginate($products, 'Products list');
    }

    #[OA\Get(
        path: '/api/v1/products/filters',
        tags: ['Products'],
        summary: 'Get product filters',
        responses: [
            new OA\Response(response: 200, description: 'Product filters'),
        ]
    )]
    public function filters(Request $request)
    {
        $searchText = trim((string) $request->query('search_txt', $request->query('searchTxt', '')));
        $category = $request->query('category');
        $subCategory = $request->query('sub_category');
        $collection = $request->query('collection', $request->query('dress_style'));
        $brand = $request->query('brand');
        $price = $request->query('price');
        $priceMin = $request->query('price_min');
        $priceMax = $request->query('price_max');
        $color = $request->query('color');
        $size = $request->query('size');

        $applyProductFilters = function ($query, array $options = []) use ($searchText, $category, $subCategory, $collection, $brand, $price, $priceMin, $priceMax, $color, $size) {
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

            if (!is_null($brand) && $brand !== '') {
                if (is_numeric($brand)) {
                    $query->where('p.brand_id', (int) $brand);
                } else {
                    $query->whereExists(function ($sub) use ($brand) {
                        $sub->selectRaw('1')
                            ->from('brands as b')
                            ->whereColumn('b.id', 'p.brand_id')
                            ->where(function ($w) use ($brand) {
                                $w->where('b.slug', $brand)
                                    ->orWhere('b.name', 'like', '%' . $brand . '%');
                            });
                    });
                }
            }

            if (!is_null($color) && $color !== '') {
                $query->whereExists(function ($sub) use ($color) {
                    $sub->selectRaw('1')
                        ->from('product_variants as pv')
                        ->whereColumn('pv.product_id', 'p.id')
                        ->where('pv.color_name', 'like', '%' . $color . '%');
                });
            }

            if (!is_null($size) && $size !== '') {
                $query->whereExists(function ($sub) use ($size) {
                    $sub->selectRaw('1')
                        ->from('product_variants as pv')
                        ->join('sizes as s', 's.id', '=', 'pv.size_id')
                        ->whereColumn('pv.product_id', 'p.id')
                        ->where(function ($where) use ($size) {
                            if (is_numeric($size)) {
                                $where->where('s.id', (int) $size);
                            } else {
                                $where->where('s.name', 'like', '%' . $size . '%');
                            }
                        });
                });
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
        // Categories Query
        $categories = DB::table('categories as c')
            ->select('c.id', 'c.name', 'c.slug', 'c.image_url')
            ->whereExists(function ($sub) use ($productIdsQuery) {
                $sub->selectRaw('1')
                    ->from('products as p')
                    ->whereColumn('p.category_id', 'c.id')
                    ->whereIn('p.id', $productIdsQuery);
            })
            ->orderBy('name')
            ->get();

        // Colors Query
        $colors = DB::table('product_variants as pv')
            ->selectRaw('DISTINCT pv.color_name as id, pv.color_name as name, pv.color as hex_code')
            ->whereIn('pv.product_id', $productIdsQuery)
            ->whereNotNull('pv.color_name')
            ->where('pv.color_name', '<>', '')
            ->orderBy('pv.color_name')
            ->get();

        //Sizes Query
        $sizes = DB::table('sizes as s')
            ->select('s.id', 's.name', 's.sort_order')
            ->join('product_variants as pv', 'pv.size_id', '=', 's.id')
            ->whereIn('pv.product_id', $productIdsQuery)
            ->distinct()
            ->orderBy('s.sort_order')
            ->orderBy('s.name')
            ->get();

        //Collection Query
        $collections = DB::table('collections as d')
            ->select('d.id', 'd.category_id', 'd.name', 'd.slug', 'd.sort_order', 'd.image_url')
            ->whereExists(function ($sub) use ($productIdsQuery) {
                $sub->selectRaw('1')
                    ->from('collection_product as cp')
                    ->join('products as p', 'p.id', '=', 'cp.product_id')
                    ->whereColumn('cp.collection_id', 'd.id')
                    ->whereIn('p.id', $productIdsQuery);
            })
            ->orderBy('d.sort_order')
            ->orderBy('d.name')
            ->get();

        //SubCategory Query
        $subCategoryGrouped = DB::table('sub_categories as sc')
            ->join('products as p', 'p.sub_category_id', '=', 'sc.id')
            ->whereIn('p.id', $productIdsQuery)
            ->selectRaw('MAX(sc.id) as sub_category_id, sc.category_id, sc.name')
            ->groupBy('sc.category_id', 'sc.name')
            ->orderBy('sc.category_id')
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

        $brands = DB::table('brands as b')
            ->select('b.id', 'b.name', 'b.slug', 'b.sort_order', 'b.image_url')
            ->whereExists(function ($sub) use ($productIdsQuery) {
                $sub->selectRaw('1')
                    ->from('products as p')
                    ->whereColumn('p.brand_id', 'b.id')
                    ->whereIn('p.id', $productIdsQuery);
            })
            ->orderBy('b.sort_order')
            ->orderBy('b.name')
            ->get();

        $collectionsByCategory = $collections->groupBy('category_id');
        $subCategoriesByCategory = $subCategories
            ->filter(fn($row) => !is_null($row->category_id))
            ->groupBy('category_id');

        $categoryTree = $categories->map(function ($parent) use ($subCategoriesByCategory, $collectionsByCategory) {
            $children = collect($subCategoriesByCategory->get($parent->id, []))->map(function ($child) use ($collectionsByCategory, $parent) {
                return (object) [
                    'id' => $child->id,
                    'name' => $child->name,
                    'slug' => $child->slug,
                    'category_id' => $child->category_id,
                    'sub_child' => collect($collectionsByCategory->get($parent->id, []))->values(),
                ];
            })->values();

            return (object) [
                'id' => $parent->id,
                'name' => $parent->name,
                'slug' => $parent->slug,
                'image_url' => $parent->image_url,
                'child' => $children,
            ];
        })->values();

        return $this->success([
            'categories' => $categories,
            'sub_categories' => $subCategories,
            'colors' => $colors,
            'sizes' => $sizes,
            'collections' => $collections,
            'brands' => $brands,
            'category_hierarchy' => [
                'parents' => $categoryTree,
            ],
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
    #[OA\Get(
        path: '/api/v1/products/{id}',
        tags: ['Products'],
        summary: 'Get product detail',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Product detail'),
            new OA\Response(response: 404, description: 'Product not found'),
        ]
    )]
    public function show(int $id)
    {
        $product = $this->productRepository->findById($id);
        if (!$product) {
            return $this->error('Product not found', 404);
        }

        return $this->success($product, 'Product detail', 200);
    }

    #[OA\Get(
        path: '/api/v1/products/{id}/detail-sections',
        tags: ['Products'],
        summary: 'Get product detail sections',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Product detail sections'),
            new OA\Response(response: 404, description: 'Product not found'),
        ]
    )]
    public function detailSections(int $id)
    {
        $product = $this->productRepository->findById($id);
        if (!$product) {
            return $this->error('Product not found', 404);
        }

        $details = [];
        if (!empty($product->desc)) {
            $details[] = [
                'key' => 'description',
                'label' => 'Description',
                'value' => (string) $product->desc,
            ];
        }
        if (!empty($product->category?->name)) {
            $details[] = [
                'key' => 'category',
                'label' => 'Category',
                'value' => (string) $product->category->name,
            ];
        }
        if (!empty($product->subCategory?->name)) {
            $details[] = [
                'key' => 'sub_category',
                'label' => 'Sub Category',
                'value' => (string) $product->subCategory->name,
            ];
        }
        if (!empty($product->brand?->name)) {
            $details[] = [
                'key' => 'brand',
                'label' => 'Brand',
                'value' => (string) $product->brand->name,
            ];
        }
        if (!empty($product->collections)) {
            $collectionNames = $product->collections
                ->pluck('name')
                ->filter()
                ->values()
                ->implode(', ');

            if ($collectionNames !== '') {
                $details[] = [
                    'key' => 'collections',
                    'label' => 'Collections',
                    'value' => $collectionNames,
                ];
            }
        }

        $totalStock = (int) $product->variants->sum('stock_quantity');
        $details[] = [
            'key' => 'total_stock',
            'label' => 'Total Stock',
            'value' => $totalStock,
        ];

        $reviews = ProductReview::query()
            ->where('product_id', $product->id)
            ->with('customer:id,full_name,user_name')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function (ProductReview $review) {
                return [
                    'id' => (int) $review->id,
                    'customer_name' => (string) ($review->customer?->full_name ?: $review->customer?->user_name ?: 'Customer'),
                    'rating' => (int) $review->rating,
                    'comment' => (string) $review->comment,
                    'created_at' => optional($review->created_at)?->toISOString(),
                ];
            })
            ->values();

        $faqs = ProductFaq::query()
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get()
            ->map(function (ProductFaq $faq) {
                return [
                    'id' => (int) $faq->id,
                    'question' => (string) $faq->question,
                    'answer' => (string) $faq->answer,
                ];
            })
            ->values();

        return $this->success([
            'product_detail' => $details,
            'rating_and_reviews' => $reviews,
            'faqs_detail' => $faqs,
        ], 'Product detail sections', 200);
    }

    #[OA\Get(
        path: '/api/v1/products/top-selling',
        tags: ['Products'],
        summary: 'Get top selling products by actual order quantity',
        responses: [
            new OA\Response(response: 200, description: 'Top selling products'),
        ]
    )]
    public function topSelling()
    {
        $limit = 8;

        $topProductIds = DB::table('order_items')
            ->join('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->select('products.id', DB::raw('SUM(order_items.quantity) as total_sold'))
            ->groupBy('products.id')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->pluck('products.id')
            ->toArray();

        if (empty($topProductIds)) {
            return $this->success([], 'Top selling products');
        }

        $products = Product::query()
            ->whereIn('id', $topProductIds)
            ->with([
                'thumbnail:id,product_id,image_url,image_type,sort_order',
                'images' => function ($q) {
                    $q->select('id', 'product_id', 'image_url', 'image_type', 'sort_order')
                        ->orderBy('sort_order');
                },
                'brand:id,name,slug,image_url',
                'subCategory:id,category_id,name,slug',
                'collections:id,name,slug',
            ])->withCount([
                'reviews as total_reviews' => function ($q) {
                    $q->select(\DB::raw('count(*)'));
                },
                'reviews as total_rating_sum' => function ($q) {
                    $q->select(\DB::raw('coalesce(sum(rating), 0)'));
                },
                'reviews as average_rating' => function ($q) {
                    $q->select(\DB::raw('coalesce(avg(rating), 0)'));
                }
            ])
            ->get();

        // Preserve the order from the topProductIds query
        $ordered = collect($topProductIds)->map(function ($id) use ($products) {
            return $products->firstWhere('id', $id);
        })->filter();

        return $this->success($ordered->values(), 'Top selling products');
    }

    #[OA\Get(
        path: '/api/v1/products/{id}/reviews',
        tags: ['Products'],
        summary: 'Get product reviews',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'rating', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 5)),
            new OA\Parameter(name: 'sort_by', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['latest', 'oldest', 'rating_high', 'rating_low'])),
            new OA\Parameter(name: 'mine_only', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Product reviews'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Product not found'),
        ]
    )]
    public function reviewByProduct(ProductReviewIndexRequest $request, int $id)
    {
        $productExists = Product::query()->whereKey($id)->exists();
        if (!$productExists) {
            return $this->error('Product not found', 404);
        }

        $filters = $request->validated();
        $reviewQuery = ProductReview::query()
            ->where('product_id', $id)
            ->with('customer:id,full_name,user_name');

        if (!empty($filters['rating'])) {
            $reviewQuery->where('rating', (int) $filters['rating']);
        }

        $mineOnly = filter_var($filters['mine_only'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($mineOnly) {
            $customer = auth()->guard('customer')->user();
            if (!$customer) {
                return $this->error('Unauthorized', 401);
            }
            $reviewQuery->where('customer_id', $customer->id);
        }

        $sortBy = $filters['sort_by'] ?? 'latest';
        if ($sortBy === 'oldest') {
            $reviewQuery->orderBy('created_at');
        } elseif ($sortBy === 'rating_high') {
            $reviewQuery->orderByDesc('rating')->orderByDesc('created_at');
        } elseif ($sortBy === 'rating_low') {
            $reviewQuery->orderBy('rating')->orderByDesc('created_at');
        } else {
            $reviewQuery->orderByDesc('created_at');
        }

        $reviews = $reviewQuery->get();
        $reviewRows = $reviews->map(function (ProductReview $review) {
            return [
                'id' => (int) $review->id,
                'customer_name' => (string) ($review->customer?->full_name ?: $review->customer?->user_name ?: 'Customer'),
                'rating' => (int) $review->rating,
                'comment' => (string) $review->comment,
                'created_at' => optional($review->created_at)?->toISOString(),
            ];
        })->values();

        $average = ProductReview::query()
            ->where('product_id', $id)
            ->avg('rating');

        return $this->success([
            'reviews' => $reviewRows,
            'total_reviews' => (int) $reviewRows->count(),
            'average_rating' => $average ? round((float) $average, 1) : 0.0,
        ], 'Product reviews', 200);
    }

    #[OA\Get(
        path: '/api/v1/products/top_review',
        tags: ['Products'],
        summary: 'Get Top 5 reviews',
        responses: [
            new OA\Response(response: 200, description: 'Product reviews'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Product not found'),
        ]
    )]
    public function topFiveReviews(Request $request)
    {
        $reviewQuery = ProductReview::query()->with('customer:id,full_name')
            ->where('rating', '>=', 3)
            ->orderBy('rating', 'desc')
            ->limit(5)
            ->get()
            ->unique('customer_id'); // Remove duplicates by customer_id

        return $this->success(
            $reviewQuery
            ,
            'All reviews',
            200
        );
    }

    #[OA\Post(
        path: '/api/v1/products/{id}/reviews',
        tags: ['Products'],
        summary: 'Create or update product review',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['rating', 'comment'],
                properties: [
                    new OA\Property(property: 'rating', type: 'integer', minimum: 1, maximum: 5),
                    new OA\Property(property: 'comment', type: 'string', minLength: 3, maxLength: 1000),
                ]
            )
        ),
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Review submitted'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function storeReview(ProductReviewStoreRequest $request, int $id)
    {
        $customer = auth()->guard('customer')->user();
        if (!$customer) {
            return $this->error('Unauthorized', 401);
        }

        $productExists = Product::query()->whereKey($id)->exists();
        if (!$productExists) {
            return $this->error('Product not found', 404);
        }

        $payload = $request->validated();
        $review = ProductReview::query()->updateOrCreate(
            [
                'product_id' => $id,
                'customer_id' => $customer->id,
            ],
            [
                'rating' => (int) $payload['rating'],
                'comment' => (string) $payload['comment'],
            ]
        );

        return $this->success([
            'id' => (int) $review->id,
            'customer_name' => (string) ($customer->full_name ?: $customer->user_name ?: 'Customer'),
            'rating' => (int) $review->rating,
            'comment' => (string) $review->comment,
            'created_at' => optional($review->created_at)?->toISOString(),
        ], 'Review submitted', 200);
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
