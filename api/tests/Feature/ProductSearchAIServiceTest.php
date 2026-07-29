<?php
namespace Tests\Feature;

use App\Jobs\PopulateProductSearchAiCache;
use App\Repositories\ProductRepository;
use App\Services\Api\V1\AI\ProductSearchAIService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class ProductSearchAIServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_dispatches_job_and_returns_fallback_on_cache_miss()
    {
        Bus::fake();
        $this->app['config']->set('cache.default', 'array');

        $message = 'blue denim jacket';

        $productRepo = Mockery::mock(ProductRepository::class);
        $paginator = new LengthAwarePaginator(['fallback-result'], 1, 15, 1);
        $productRepo->shouldReceive('getAll')
            ->once()
            ->with(['search_txt' => $message])
            ->andReturn($paginator);

        $service = new ProductSearchAIService($productRepo);
        $result = $service->productAiFilter($message);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(['fallback-result'], $result->items());

        Bus::assertDispatched(PopulateProductSearchAiCache::class);
    }

    public function test_returns_cached_products_and_does_not_dispatch_job()
    {
        Bus::fake();
        $this->app['config']->set('cache.default', 'array');

        $message = 'jackets';
        $hash = sha1($message);
        $productsKey = "ai_products:{$hash}";

        $cachedPaginator = new LengthAwarePaginator(['cached-result'], 1, 15, 1);
        Cache::put($productsKey, $cachedPaginator, 3600);

        $productRepo = Mockery::mock(ProductRepository::class);
        $productRepo->shouldNotReceive('getAll');

        $service = new ProductSearchAIService($productRepo);
        $result = $service->productAiFilter($message);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(['cached-result'], $result->items());

        Bus::assertNotDispatched(PopulateProductSearchAiCache::class);
    }
}
