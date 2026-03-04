<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderLifecycleRepository
{
    public function listForCustomer(int $customerId, array $filters = []): LengthAwarePaginator
    {
        return Order::query()
            ->where('customer_id', $customerId)
            ->when(isset($filters['status']) && $filters['status'] !== '', function ($q) use ($filters) {
                $q->where('status', $filters['status']);
            })
            ->when(isset($filters['payment_state']) && $filters['payment_state'] !== '', function ($q) use ($filters) {
                $q->where('payment_state', $filters['payment_state']);
            })
            ->when(isset($filters['payment_status']) && $filters['payment_status'] !== '', function ($q) use ($filters) {
                $q->where('payment_status', $filters['payment_status']);
            })
            ->when(isset($filters['order_status']) && $filters['order_status'] !== '', function ($q) use ($filters) {
                $q->where('order_status', $filters['order_status']);
            })
            ->with(['items.variant.product:id,name,slug', 'voucher:id,code,name'])
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function listForAdmin(array $filters = []): LengthAwarePaginator
    {
        return Order::query()
            ->when(isset($filters['status']) && $filters['status'] !== '', function ($q) use ($filters) {
                $q->where('status', $filters['status']);
            })
            ->when(isset($filters['payment_state']) && $filters['payment_state'] !== '', function ($q) use ($filters) {
                $q->where('payment_state', $filters['payment_state']);
            })
            ->when(isset($filters['payment_status']) && $filters['payment_status'] !== '', function ($q) use ($filters) {
                $q->where('payment_status', $filters['payment_status']);
            })
            ->when(isset($filters['order_status']) && $filters['order_status'] !== '', function ($q) use ($filters) {
                $q->where('order_status', $filters['order_status']);
            })
            ->when(isset($filters['customer_id']) && $filters['customer_id'] !== '', function ($q) use ($filters) {
                $q->where('customer_id', (int) $filters['customer_id']);
            })
            ->with(['items.variant.product:id,name,slug', 'voucher:id,code,name'])
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 20));
    }

    public function findForCustomer(int $orderId, int $customerId): ?Order
    {
        return Order::query()
            ->whereKey($orderId)
            ->where('customer_id', $customerId)
            ->with(['items.variant.product:id,name,slug', 'voucher:id,code,name'])
            ->first();
    }

    public function findForAdmin(int $orderId): ?Order
    {
        return Order::query()
            ->whereKey($orderId)
            ->with(['items.variant.product:id,name,slug', 'voucher:id,code,name'])
            ->first();
    }

    public function cancelByCustomer(int $orderId, int $customerId): Order
    {
        return DB::transaction(function () use ($orderId, $customerId) {
            $order = Order::whereKey($orderId)
                ->where('customer_id', $customerId)
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

            $nextStatus = $order->payment_state === 'paid' ? 'refunded' : 'cancelled';
            $this->transition($order, $nextStatus);

            return $order->fresh(['items.variant.product:id,name,slug', 'voucher:id,code,name']);
        });
    }

    public function updateStatusByAdmin(int $orderId, string $targetStatus): Order
    {
        return DB::transaction(function () use ($orderId, $targetStatus) {
            $order = Order::whereKey($orderId)->lockForUpdate()->first();
            if (!$order) {
                throw ValidationException::withMessages([
                    'order' => ['Order not found'],
                ]);
            }

            $this->transition($order, $targetStatus);

            return $order->fresh(['items.variant.product:id,name,slug', 'voucher:id,code,name']);
        });
    }

    public function transition(Order $order, string $targetStatus): void
    {
        $allowed = [
            'pending' => ['paid', 'processing', 'cancelled'],
            'paid' => ['processing', 'cancelled', 'refunded'],
            'processing' => ['shipped', 'cancelled', 'refunded'],
            'shipped' => ['completed', 'refunded'],
            'completed' => ['refunded'],
            'cancelled' => [],
            'refunded' => [],
        ];

        $current = $order->status ?: 'pending';
        if ($current === $targetStatus) {
            return;
        }

        if (!in_array($targetStatus, $allowed[$current] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => ["Invalid transition from {$current} to {$targetStatus}"],
            ]);
        }

        $order->status = $targetStatus;

        if ($targetStatus === 'paid') {
            $order->payment_state = 'paid';
            $order->payment_status = 'paid';
            $order->paid_at = $order->paid_at ?? now();
        }

        if ($targetStatus === 'processing' && $order->payment_state !== 'paid') {
            $order->payment_state = 'paid';
            $order->payment_status = 'paid';
            $order->paid_at = $order->paid_at ?? now();
        }

        if ($targetStatus === 'cancelled') {
            $order->payment_state = $order->payment_state === 'paid' ? 'refunded' : 'cancelled';
            $order->payment_status = 'failed';
            $order->cancelled_at = $order->cancelled_at ?? now();
            $this->restoreStockIfNeeded($order);
        }

        if ($targetStatus === 'refunded') {
            $order->payment_state = 'refunded';
            $order->payment_status = 'failed';
            $order->refunded_at = $order->refunded_at ?? now();
            $this->restoreStockIfNeeded($order);
        }

        $order->save();
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
}
