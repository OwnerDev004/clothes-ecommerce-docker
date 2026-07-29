<?php
namespace App\Services\Api\V1\AI;

use App\Repositories\ProductRepository;
use App\Jobs\PopulateProductSearchAiCache;
use Illuminate\Support\Facades\Cache;


class ProductSearchAIService
{
    protected $productRepository;

    public function __construct(ProductRepository $productRepo)
    {
        $this->productRepository = $productRepo;
    }

    /**
     * Convert user message into product filters using cached AI results when available.
     * On cache miss, dispatch a background job to populate the cache and return a lightweight fallback result.
     */
    public function productAiFilter(string $message)
    {
        $ttl = env('AI_SEARCH_CACHE_TTL', 3600);
        $hash = sha1($message);
        $filtersKey = "ai_filters:{$hash}";
        $productsKey = "ai_products:{$hash}";

        // If we have cached products for this query, return them immediately
        if (Cache::has($productsKey)) {
            return Cache::get($productsKey);
        }

        // If we have cached filters but not cached products, use filters to query DB and cache products
        if (Cache::has($filtersKey)) {
            $filters = Cache::get($filtersKey);
            $products = $this->productRepository->getAll($filters);
            Cache::put($productsKey, $products, $ttl);
            return $products;
        }

        // Cache miss: dispatch a job to call the AI and populate the cache asynchronously
        PopulateProductSearchAiCache::dispatch($message, $filtersKey, $productsKey, $ttl);

        // Return a fast fallback based on raw search text to avoid blocking user
        $fallback = $this->productRepository->getAll(['search_txt' => $message]);
        return $fallback;
    }
}
