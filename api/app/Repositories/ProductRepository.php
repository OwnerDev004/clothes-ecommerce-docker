<?php

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;

class ProductRepository extends BaseRepository
{

    public function __construct(Product $model)
    {
        $this->model = $model;
    }

    public function getAll(array $filters = []): Collection
    {
        $normalized = array_filter($filters, function ($value) {
            return $value !== null && $value !== '';
        });
        ksort($normalized);
        $cacheKey = 'products:getAll:' . md5(json_encode($normalized));
        $ttlSeconds = 300;

        return Cache::remember($cacheKey, $ttlSeconds, function () use ($filters) {
            $searchText = trim((string) ($filters['search_txt'] ?? ''));
            $category = $filters['category'] ?? null;
            $price = $filters['price'] ?? null;
            $color = $filters['color'] ?? null;
            $size = $filters['size'] ?? null;
            $dressStyle = $filters['dress_style'] ?? null;

            $query = $this->model->newQuery()->select('products.*')->with([
                'thumbnail:id,product_id,image_url,image_type,sort_order',
                'images' => function ($q) {
                    $q->select('id', 'product_id', 'image_url', 'image_type', 'sort_order')
                        ->orderBy('sort_order');
                },
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
                            ->where(function ($w) use ($category) {
                                $w->where('categories.slug', $category)
                                    ->orWhere('categories.name', 'like', '%' . $category . '%');
                            });
                    });
                }
            }

            if (!is_null($dressStyle) && $dressStyle !== '') {
                if (is_numeric($dressStyle)) {
                    $query->where('products.dress_type_id', (int) $dressStyle);
                } else {
                    $query->whereExists(function (Builder $sub) use ($dressStyle) {
                        $sub->selectRaw('1')
                            ->from('dress_types')
                            ->whereColumn('dress_types.id', 'products.dress_type_id')
                            ->where(function ($w) use ($dressStyle) {
                                $w->where('dress_types.slug', $dressStyle)
                                    ->orWhere('dress_types.name', 'like', '%' . $dressStyle . '%');
                            });
                    });
                }
            }

            if (!is_null($color) && $color !== '') {
                $query->whereExists(function (Builder $sub) use ($color) {
                    $sub->selectRaw('1')
                        ->from('product_variants')
                        ->join('colors', 'colors.id', '=', 'product_variants.color_id')
                        ->whereColumn('product_variants.product_id', 'products.id')
                        ->where(function ($w) use ($color) {
                            if (is_numeric($color)) {
                                $w->where('colors.id', (int) $color);
                            } else {
                                $w->where('colors.name', 'like', '%' . $color . '%')
                                    ->orWhere('colors.hex_code', $color);
                            }
                        });
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

            return $query->orderByDesc('products.id')->get();
        });
    }
}
