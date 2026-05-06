<?php

namespace App\Services\Api\V1\Admin;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Repositories\AppSettingRepository;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    private const CACHE_KEY = 'admin:dashboard:summary';
    private const CACHE_TTL_SECONDS = 60;

    public function __construct(
        private readonly AppSettingRepository $settingRepository,
    ) {
    }

    public function summary(): array
    {
        $settings = $this->settingRepository->current();
        $lowStockThreshold = max(0, (int) ($settings->low_stock_threshold ?? 5));

        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function () use ($lowStockThreshold) {
            $today = now()->startOfDay();
            $weekStart = now()->startOfWeek();
            $weekEnd = now()->endOfWeek();

            $revenueToday = (float) Order::query()
                ->whereDate('order_date', $today)
                ->where('payment_status', 'paid')
                ->sum('total_price');

            $revenueThisWeek = (float) Order::query()
                ->whereBetween('order_date', [$weekStart, $weekEnd])
                ->where('payment_status', 'paid')
                ->sum('total_price');

            $weeklySales = $this->buildWeeklySalesTrend();
            $recentOrders = $this->buildRecentOrders();
            $topCategories = $this->buildTopCategories();
            $lowStockItems = $this->buildLowStockItems($lowStockThreshold);
            $statusBreakdown = $this->buildStatusBreakdown();

            return [
                'stats' => [
                    'revenue_today' => $revenueToday,
                    'revenue_this_week' => $revenueThisWeek,
                    'pending_orders' => Order::query()->where('status', 'pending')->count(),
                    'active_products' => Product::query()->count(),
                    'low_stock_items' => ProductVariant::query()
                        ->where('stock_quantity', '<=', $lowStockThreshold)
                        ->count(),
                    'customers' => Customer::query()->count(),
                ],
                'trend' => $weeklySales,
                'status_breakdown' => $statusBreakdown,
                'recent_orders' => $recentOrders,
                'top_categories' => $topCategories,
                'low_stock_items' => $lowStockItems,
                'activity' => $this->buildActivityFeed($recentOrders, $lowStockItems),
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * @return array<int, array{date:string,total:float}>
     */
    protected function buildWeeklySalesTrend(): array
    {
        $start = now()->subDays(6)->startOfDay();
        $end = now()->endOfDay();

        $salesByDate = Order::query()
            ->selectRaw('DATE(order_date) as sale_date, COALESCE(SUM(total_price), 0) as total_sales')
            ->where('payment_status', 'paid')
            ->whereBetween('order_date', [$start, $end])
            ->groupByRaw('DATE(order_date)')
            ->orderByRaw('DATE(order_date)')
            ->pluck('total_sales', 'sale_date');

        $days = collect(range(0, 6))->map(function (int $offset) {
            return now()->subDays(6 - $offset)->toDateString();
        });

        return $days->map(function (string $date) use ($salesByDate) {
            return [
                'date' => $date,
                'total' => (float) ($salesByDate[$date] ?? 0),
            ];
        })->values()->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildRecentOrders(): array
    {
        return Order::query()
            ->with(['customer:id,full_name,user_name'])
            ->withCount('items')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(function (Order $order) {
                return [
                    'id' => $order->id,
                    'customer' => $order->customer?->full_name
                        ?? $order->customer?->user_name
                        ?? 'Customer',
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'amount' => (float) $order->total_price,
                    'item_count' => (int) $order->items_count,
                    'updated_at' => optional($order->updated_at)->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildTopCategories(): array
    {
        $categories = Category::query()
            ->withCount('products')
            ->orderByDesc('products_count')
            ->orderBy('name')
            ->limit(5)
            ->get();

        $totalProducts = max((int) Product::query()->count(), 1);

        return $categories->map(function (Category $category) use ($totalProducts) {
            $productCount = (int) $category->products_count;

            return [
                'id' => $category->id,
                'name' => $category->name,
                'product_count' => $productCount,
                'share' => round(($productCount / $totalProducts) * 100, 1),
            ];
        })->values()->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildLowStockItems(int $threshold): array
    {
        return ProductVariant::query()
            ->with(['product:id,name,slug', 'size:id,name'])
            ->where('stock_quantity', '<=', $threshold)
            ->orderBy('stock_quantity')
            ->limit(10)
            ->get()
            ->map(function (ProductVariant $variant) {
                return [
                    'id' => $variant->id,
                    'product_id' => $variant->product_id,
                    'product_name' => $variant->product?->name ?? 'Product',
                    'size' => $variant->size?->name,
                    'color' => $variant->color,
                    'stock_quantity' => (int) $variant->stock_quantity,
                    'sell_price' => (float) $variant->sell_price,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{status:string,count:int}>
     */
    protected function buildStatusBreakdown(): array
    {
        $statuses = Order::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect([
            'pending',
            'paid',
            'processing',
            'shipped',
            'completed',
            'cancelled',
            'refunded',
        ])->map(function (string $status) use ($statuses) {
            return [
                'status' => $status,
                'count' => (int) ($statuses[$status] ?? 0),
            ];
        })->values()->all();
    }

    /**
     * @param array<int, array<string, mixed>> $recentOrders
     * @param array<int, array<string, mixed>> $lowStockItems
     * @return array<int, array<string, mixed>>
     */
    protected function buildActivityFeed(array $recentOrders, array $lowStockItems): array
    {
        $activity = collect();

        foreach (array_slice($recentOrders, 0, 3) as $order) {
            $activity->push([
                'type' => 'order',
                'title' => sprintf('Order #%s updated', $order['id']),
                'detail' => sprintf('Customer %s is now %s.', $order['customer'], $order['status']),
                'meta' => $order['updated_at'],
            ]);
        }

        foreach (array_slice($lowStockItems, 0, 3) as $item) {
            $activity->push([
                'type' => 'inventory',
                'title' => sprintf('%s needs restock', $item['product_name']),
                'detail' => sprintf('Only %d units left in stock.', $item['stock_quantity']),
                'meta' => now()->toIso8601String(),
            ]);
        }

        return $activity->take(6)->values()->all();
    }
}
