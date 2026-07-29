<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\SubCategory;
use App\Repositories\BaseRepository;
use DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;

class ProductRepository extends BaseRepository
{
    private const PUBLIC_LIST_VERSION_KEY = 'products:public:getAll:version';
    private const PUBLIC_LIST_TTL_SECONDS = 300;

    public function __construct(Product $model)
    {
        $this->model = $model;
    }

    public function getAll(array $filters = []): LengthAwarePaginator
    {

        $normalized = array_filter($filters, function ($value) {
            return $value !== null && $value !== '';
        });
        ksort($normalized);
        $cacheVersion = self::getPublicListCacheVersion();
        $cacheKey = 'products:public:getAll:v' . $cacheVersion . ':' . md5(json_encode($normalized));
        $requestedPage = (int) ($filters['page'] ?? 1);
        $shouldCache = $requestedPage === 1;
        $isUnfilteredRequest = empty(array_diff_key($normalized, array_flip(['page', 'per_page'])));

        $queryBuilder = function () use ($filters) {
            $searchText = trim((string) ($filters['search_txt'] ?? ''));
            $category = $filters['category'] ?? null;
            $subCategory = $filters['sub_category'] ?? null;
            $price = $filters['price'] ?? null;
            $priceMin = $filters['price_min'] ?? null;
            $priceMax = $filters['price_max'] ?? null;
            $color = $filters['color'] ?? null;
            $size = $filters['size'] ?? null;
            $brand = $filters['brand'] ?? null;
            $collection = $filters['collection'] ?? ($filters['dress_style'] ?? null);
            $newArrivals = !empty($filters['new_arrivals']);
            $sortBy = trim((string) ($filters['sort_by'] ?? 'latest'));

            $query = $this->model->newQuery()->select('products.*')->with([
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
                    ]);

            if ($searchText !== '') {
                $query->where(function ($q) use ($searchText) {
                    $q->where('products.name', 'like', '%' . $searchText . '%')
                        ->orWhere('products.sku', 'like', '%' . $searchText . '%')
                        ->orWhere('products.slug', 'like', '%' . $searchText . '%')
                        ->orWhere('products.desc', 'like', '%' . $searchText . '%');
                });
            }

            if (!is_null($category) && $category !== '') {
                if (is_numeric($category)) {
                    $query->where('products.category_id', (int) $category);
                } else {
                    $query->whereExists(function (Builder $sub) use ($category) {
                        $sub->selectRaw('1')
                            ->from('categories')
                            ->whereColumn('categories.id', 'products.category_id')
                            ->where('categories.slug', $category);
                    });
                }
            }

            if (!is_null($collection) && $collection !== '') {
                if (is_numeric($collection)) {
                    $query->whereExists(function (Builder $sub) use ($collection) {
                        $sub->selectRaw('1')
                            ->from('collection_product')
                            ->whereColumn('collection_product.product_id', 'products.id')
                            ->where('collection_product.collection_id', (int) $collection);
                    });
                } else {
                    $query->whereExists(function (Builder $sub) use ($collection) {
                        $sub->selectRaw('1')
                            ->from('collection_product')
                            ->join('collections', 'collections.id', '=', 'collection_product.collection_id')
                            ->whereColumn('collection_product.product_id', 'products.id')
                            ->where(function ($w) use ($collection) {
                                $w->where('collections.slug', $collection)
                                    ->orWhere('collections.name', 'like', '%' . $collection . '%');
                            });
                    });
                }
            }

            if (!is_null($subCategory) && $subCategory !== '') {
                if (is_numeric($subCategory)) {
                    $query->where('products.sub_category_id', (int) $subCategory);
                } else {
                    $query->whereExists(function (Builder $sub) use ($subCategory) {
                        $test = $sub->selectRaw('1')
                            ->from('sub_categories')
                            ->whereColumn('sub_categories.id', 'products.sub_category_id')
                            ->where('sub_categories.slug', $subCategory);
                    });
                }
            }


            if (!is_null($brand) && $brand !== '') {
                if (is_numeric($brand)) {
                    $query->where('products.brand_id', (int) $brand);
                } else {
                    $query->whereExists(function (Builder $sub) use ($brand) {
                        $sub->selectRaw('1')
                            ->from('brands')
                            ->whereColumn('brands.id', 'products.brand_id')
                            ->where(function ($w) use ($brand) {
                                $w->where('brands.slug', $brand)
                                    ->orWhere('brands.name', 'like', '%' . $brand . '%');
                            });
                    });
                }
            }

            if (!is_null($color) && $color !== '') {
                $query->whereExists(function (Builder $sub) use ($color) {
                    $sub->selectRaw('1')
                        ->from('product_variants')
                        ->whereColumn('product_variants.product_id', 'products.id')
                        ->where('product_variants.color_name', 'like', '%' . $color . '%');
                });
            }

            if (!is_null($size) && $size !== '') {
                $query->whereExists(function (Builder $sub) use ($size) {
                    $sub->selectRaw('1')
                        ->from('product_variants')
                        ->join('sizes', 'sizes.id', '=', 'product_variants.size_id')
                        ->whereColumn('product_variants.product_id', 'products.id')
                        ->where(function ($w) use ($size) {
                            if (is_numeric($size)) {
                                $w->where('sizes.id', (int) $size);
                            } else {
                                $w->where('sizes.name', 'like', '%' . $size . '%');
                            }
                        });
                });
            }

            if (!is_null($price) && $price !== '') {
                $query->where('products.price', '<=', (float) $price);
            }

            if (!is_null($priceMin) && $priceMin !== '') {
                $query->where('products.price', '>=', (float) $priceMin);
            }

            if (!is_null($priceMax) && $priceMax !== '') {
                $query->where('products.price', '<=', (float) $priceMax);
            }

            if ($newArrivals) {
                $query->where('products.created_at', '>=', now()->subDays(30));
            }

            match ($sortBy) {
                'oldest' => $query->orderBy('products.id'),
                'price_low' => $query->orderBy('products.price'),
                'price_high' => $query->orderByDesc('products.price'),
                'name_asc' => $query->orderBy('products.name'),
                'name_desc' => $query->orderByDesc('products.name'),
                default => $query->orderByDesc('products.id'),
            };

            $perPage = (int) ($filters['per_page'] ?? 12);
            if ($perPage < 1) {
                $perPage = 12;
            }
            if ($perPage > 50) {
                $perPage = 50;
            }
            $sql = $query->toSql();
            $bindings = $query->getBindings();
            \Log::info('Product query SQL', ['sql' => $sql, 'bindings' => $bindings]);
            return $query->paginate($perPage);
        };

        if (!$shouldCache) {
            return $queryBuilder();
        }

        $cached = Cache::get($cacheKey);
        if (!is_null($cached)) {
            return $cached;
        }

        $result = $queryBuilder();

        // Prevent poisoning cache with an accidental empty first-page unfiltered response.
        if ($isUnfilteredRequest && $result->total() === 0) {
            return $result;
        }

        Cache::put($cacheKey, $result, self::PUBLIC_LIST_TTL_SECONDS);
        return $result;
    }

    // filter product by AI service
    public function filterProductByAI($filters = []): LengthAwarePaginator
    {
        // AI and the normal catalog must share exactly the same filter semantics.
        return $this->getAll(array_merge(['per_page' => 12], $filters));
    }

    public static function bumpPublicListCacheVersion(): void
    {
        if (!Cache::has(self::PUBLIC_LIST_VERSION_KEY)) {
            Cache::forever(self::PUBLIC_LIST_VERSION_KEY, 1);
        }
        Cache::increment(self::PUBLIC_LIST_VERSION_KEY);
    }

    private static function getPublicListCacheVersion(): int
    {
        $version = Cache::get(self::PUBLIC_LIST_VERSION_KEY);
        if (is_null($version)) {
            Cache::forever(self::PUBLIC_LIST_VERSION_KEY, 1);
            return 1;
        }

        return (int) $version;
    }

    public function findById(int $id): ?Product
    {
        return $this->model->newQuery()
            ->with([
                'category:id,name,slug',
                'subCategory:id,category_id,name,slug',
                'collections:id,name,slug',
                'brand:id,name,slug,image_url',
                'thumbnail:id,product_id,image_url,image_type,sort_order',
                'images' => function ($q) {
                    $q->select('id', 'product_id', 'image_url', 'image_type', 'sort_order')
                        ->orderBy('sort_order');
                },
                'variants' => function ($q) {
                    $q->select('id', 'product_id', 'color', 'color_label', 'color_name', 'size_id', 'stock_quantity', 'sell_price', 'cost_price')
                        ->with([
                            'size:id,name,sort_order',
                        ]);
                },
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
            ->find($id);
    }
}
