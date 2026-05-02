<?php

namespace App\Repositories;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\ProductVariant;
use App\Services\Api\V1\OrderRealtimeAlertService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderLifecycleRepository
{
    public function __construct(
        private readonly OrderRealtimeAlertService $orderRealtimeAlertService,
    ) {
    }

    public function listForCustomer(int $customerId, array $filters = []): LengthAwarePaginator
    {
        return Order::query()
            ->where('customer_id', $customerId)
            ->when(isset($filters['status']) && $filters['status'] !== '', function ($q) use ($filters) {
                $q->where('status', $filters['status']);
            })
            ->when(isset($filters['payment_status']) && $filters['payment_status'] !== '', function ($q) use ($filters) {
                $q->where('payment_status', $filters['payment_status']);
            })
            ->with([
                'customer:id,full_name,user_name,email,phone,address',
                'items.variant.product:id,name,slug',
                'items.variant.size:id,name',
                'voucher:id,code,name',
                'paymentTransactions',
            ])
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function listForAdmin(array $filters = []): LengthAwarePaginator
    {
        return Order::query()
            ->when(isset($filters['search_txt']) && $filters['search_txt'] !== '', function ($q) use ($filters) {
                $search = trim((string) $filters['search_txt']);

                $q->where(function ($query) use ($search) {
                    if (is_numeric($search)) {
                        $query->orWhereKey((int) $search);
                    }

                    $query->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('full_name', 'like', '%' . $search . '%')
                            ->orWhere('user_name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    });
                });
            })
            ->when(isset($filters['status']) && $filters['status'] !== '', function ($q) use ($filters) {
                $q->where('status', $filters['status']);
            })
            ->when(isset($filters['payment_status']) && $filters['payment_status'] !== '', function ($q) use ($filters) {
                $q->where('payment_status', $filters['payment_status']);
            })
            ->when(isset($filters['customer_id']) && $filters['customer_id'] !== '', function ($q) use ($filters) {
                $q->where('customer_id', (int) $filters['customer_id']);
            })
            ->with([
                'customer:id,full_name,user_name,email,phone,address',
                'items.variant.product:id,name,slug',
                'items.variant.size:id,name',
                'voucher:id,code,name',
                'paymentTransactions',
            ])
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 20));
    }

    public function findForCustomer(int $orderId, int $customerId): ?Order
    {
        return Order::query()
            ->whereKey($orderId)
            ->where('customer_id', $customerId)
            ->with([
                'customer:id,full_name,user_name,email,phone,address',
                'items.variant.product.images',
                'items.variant.size:id,name',
                'voucher:id,code,name',
                'statusHistories',
            ])
            ->first();
    }

    public function findForAdmin(int $orderId): ?Order
    {
        return Order::query()
            ->whereKey($orderId)
            ->with([
                'customer:id,full_name,user_name,email,phone,address',
                'items.variant.product:id,name,slug',
                'items.variant.product.images',
                'items.variant.product.thumbnail',
                'items.variant.size:id,name',
                'voucher:id,code,name',
                'paymentTransactions',
                'statusHistories',
            ])
            ->first();
    }

    public function cancelByCustomer(int $orderId, int $customerId): Order
    {
        return DB::transaction(function () use ($orderId, $customerId) {
            $order = Order::whereKey($orderId)
                ->where('customer_id', $customerId)
                ->with('customer:id,full_name,user_name,email,phone,address')
                ->lockForUpdate()
                ->first();

            if (!$order) {
                throw ValidationException::withMessages([
                    'order' => ['Order not found'],
                ]);
            }

            if (!in_array($order->status, ['pending', 'paid'], true)) {
                throw ValidationException::withMessages([
                    'status' => ['Order cannot be cancelled in current status'],
                ]);
            }

            $nextStatus = $order->payment_status === 'paid' ? 'refunded' : 'cancelled';
            $this->transition($order, $nextStatus, [
                'action_type' => 'customer_cancel',
                'actor_type' => Customer::class,
                'actor_id' => $order->customer_id,
                'actor_name' => $order->customer?->full_name
                    ?: $order->customer?->user_name
                    ?: $order->customer?->email
                    ?: 'Customer',
            ]);

            $this->orderRealtimeAlertService->notifyAdminOrderCancelled(
                $order->fresh([
                    'customer:id,full_name,user_name,email,phone,address',
                    'items.variant.product:id,name,slug',
                    'items.variant.size:id,name',
                    'voucher:id,code,name',
                    'paymentTransactions',
                    'statusHistories',
                ])
            );

            return $order->fresh([
                'customer:id,full_name,user_name,email,phone,address',
                'items.variant.product:id,name,slug',
                'items.variant.size:id,name',
                'voucher:id,code,name',
                'paymentTransactions',
                'statusHistories',
            ]);
        });
    }

    public function updateStatusByAdmin(int $orderId, string $targetStatus, array $context = []): Order
    {

        return DB::transaction(function () use ($orderId, $targetStatus, $context) {
            $order = Order::whereKey($orderId)->lockForUpdate()->first();
            if (!$order) {
                throw ValidationException::withMessages([
                    'order' => ['Order not found'],
                ]);
            }


            $this->transition($order, $targetStatus, $context);

            return $order->fresh([
                'customer:id,full_name,user_name,email,phone,address',
                'items.variant.product:id,name,slug',
                'items.variant.size:id,name',
                'voucher:id,code,name',
                'paymentTransactions',
                'statusHistories',
            ]);
        });
    }

    public function updateOrderDetails(int $orderId, array $data): Order
    {
        return DB::transaction(function () use ($orderId, $data) {
            $order = Order::whereKey($orderId)->lockForUpdate()->first();

            if (!$order) {
                throw ValidationException::withMessages([
                    'order' => ['Order not found'],
                ]);
            }

            $shippingProvince = trim((string) ($data['shipping_province'] ?? ''));
            $shippingFee = $this->calculateShippingFee($shippingProvince);
            $subtotal = (float) ($order->subtotal_price ?? 0);
            $discount = (float) ($order->discount_amount ?? 0);

            $order->shipping_province = $shippingProvince;
            $order->shipping_fee = $shippingFee;
            $order->shipping_address = $data['shipping_address'] ?? null;
            $order->shipping_phone = $data['shipping_phone'] ?? null;
            $order->order_note = $data['order_note'] ?? null;
            $order->total_price = round(max(0, $subtotal - $discount + $shippingFee), 2);
            $order->save();

            return $order->fresh([
                'customer:id,full_name,user_name,email,phone,address',
                'items.variant.product:id,name,slug',
                'items.variant.size:id,name',
                'voucher:id,code,name',
                'paymentTransactions',
                'statusHistories',
            ]);
        });
    }

    public function transition(Order $order, string $targetStatus, array $context = []): void
    {

        $allowed = [
            'order_confirming' => ['payment_confirmed', 'processing', 'cancelled'],
            'payment_confirmed' => ['processing', 'shipped', 'cancelled', 'refunded'],     // Added comma after 'shipping'
            'processing' => ['shipped', 'cancelled', 'refunded'],     // Added comma after 'shipping'
            'shipped' => ['delivered', 'cancelled', 'refunded'],
            'delivered' => ['refunded'],                                // Completed state
            'cancelled' => [],
            'refunded' => [],
        ];

        $current = $order->status ?: 'order_confirming';


        if ($current === $targetStatus) {
            return;
        }
        // protection status order update
        if (!in_array($targetStatus, $allowed[$current] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => ["Invalid transition from {$current} to {$targetStatus}"],
            ]);
        }

        $fromStatus = $current;
        $order->status = $targetStatus;

        if ($targetStatus === 'payment_confirmed' && $order->payment_status == 'pending') {
            $order->payment_status = 'paid';
            $order->status = $targetStatus;
            $order->paid_at = $order->paid_at ?? now();
        }
        if ($targetStatus === 'paid' && $order->payment_status == 'paid') {
            $order->status = $targetStatus;
        }
        //shipped
        if ($targetStatus === 'shipped' && $order->payment_status == 'paid') {
            $order->status = $targetStatus;
        }
        // delivered
        if ($targetStatus === 'delivered' && $order->payment_status == 'paid') {
            $order->status = $targetStatus;
        }

        if ($targetStatus === 'cancelled') {
            $order->status = 'cancelled';
            $order->payment_status = $order->payment_status === 'paid' ? 'refunded' : 'cancelled';
            $order->cancelled_at = $order->cancelled_at ?? now();
            $this->restoreStockIfNeeded($order);
        }

        if ($targetStatus === 'refunded') {
            $order->payment_status = $targetStatus;
            $order->refunded_at = $order->refunded_at ?? now();
            $this->restoreStockIfNeeded($order);
        }

        $order->save();
        $this->recordStatusHistory($order, $fromStatus, $targetStatus, $context);

        if ($targetStatus === 'processing') {
            $this->orderRealtimeAlertService->notifyCustomerProcessing($order->fresh([
                'customer:id,full_name,user_name,email,phone,address,telegram_chat_id,telegram_user_id,telegram_username,enable_telegram_alerts',
            ]));
        }

        if ($targetStatus === 'shipped') {
            $this->orderRealtimeAlertService->notifyCustomerShipped($order->fresh([
                'customer:id,full_name,user_name,email,phone,address,telegram_chat_id,telegram_user_id,telegram_username,enable_telegram_alerts',
            ]));
        }

        if ($targetStatus === 'delivered') {
            $this->orderRealtimeAlertService->notifyCustomerDelivered($order->fresh([
                'customer:id,full_name,user_name,email,phone,address,telegram_chat_id,telegram_user_id,telegram_username,enable_telegram_alerts',
            ]));
        }
    }

    public function recordStatusHistory(Order $order, string $fromStatus, string $toStatus, array $context = []): OrderStatusHistory
    {
        return OrderStatusHistory::create([
            'order_id' => $order->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'action_type' => (string) ($context['action_type'] ?? $this->resolveActionType($toStatus)),
            'reason' => $context['reason'] ?? null,
            'actor_type' => $context['actor_type'] ?? null,
            'actor_id' => $context['actor_id'] ?? null,
            'actor_name' => $context['actor_name'] ?? null,
        ]);
    }

    private function resolveActionType(string $toStatus): string
    {
        return match ($toStatus) {
            'paid' => 'payment_confirmed',
            'processing' => 'processing_started',
            'shipped' => 'shipping_confirmed',
            'completed' => 'delivery_confirmed',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            default => 'status_update',
        };
    }

    public function restoreStockIfNeeded(Order $order): void
    {
        if ($order->stock_restored_at) {
            return;
        }

        $order->loadMissing('items');
        foreach ($order->items as $item) {
            ProductVariant::whereKey($item->product_variant_id)
                ->increment('stock_quantity', (int) $item->quantity);
        }

        $order->stock_restored_at = now();
        $order->save();
    }

    private function calculateShippingFee(string $shippingProvince): float
    {
        $province = strtolower(trim($shippingProvince));
        $rates = [
            'phnom_penh' => 1.50,
            'kandal' => 2.00,
            'siem_reap' => 2.50,
            'battambang' => 2.50,
            'preah_sihanouk' => 3.00,
        ];

        return $rates[$province] ?? 3.50;
    }
}
