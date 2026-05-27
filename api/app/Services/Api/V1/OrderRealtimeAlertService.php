<?php

namespace App\Services\Api\V1;

use App\Events\Orders\AdminOrderRealtimeAlert;
use App\Events\Orders\CustomerOrderRealtimeAlert;
use App\Models\Order;
use App\Services\Api\V1\Queue\BeamsSendingService;
use App\Services\Api\V1\Queue\TelegramSendingService;
use Illuminate\Support\Facades\DB;

class OrderRealtimeAlertService
{
    public function __construct(
        private readonly BeamsSendingService $beamsSendingService,
        private readonly TelegramSendingService $telegramSendingService,
    ) {
    }

    public function notifyAdminOrderCreated(Order $order): void
    {
        event(new AdminOrderRealtimeAlert($this->buildAdminPayload(
            $order,
            'order_created',
            'New order received',
            'A customer placed a new order and is waiting for review.',
        )));
    }

    public function notifyAdminOrderCancelled(Order $order): void
    {
        event(new AdminOrderRealtimeAlert($this->buildAdminPayload(
            $order,
            'order_cancelled',
            'Order cancelled',
            'A customer cancelled an order and the dashboard needs a live refresh.',
        )));
    }

    public function notifyCustomerProcessing(Order $order): void
    {
        $message = 'Your order has been received. We are packing it now.';
        $this->broadcastCustomerUpdate($order, 'processing', 'Order processing', $message);
        DB::afterCommit(function () use ($order, $message) {
            $this->beamsSendingService->sendOrderStatusUpdate(
                $order->fresh(),
                'Order processing',
                $message,
                'processing',
            );
            $this->telegramSendingService->sendOrderStatusUpdate($order->fresh(), $message);
        });
    }

    public function notifyCustomerShipped(Order $order): void
    {
        $trackingId = (string) ($order->payment_reference ?: $order->id);
        $message = "Your order has shipped. Tracking ID: {$trackingId}.";
        $this->broadcastCustomerUpdate($order, 'shipped', 'Order shipped', $message);
        DB::afterCommit(function () use ($order, $message) {
            $this->beamsSendingService->sendOrderStatusUpdate(
                $order->fresh(),
                'Order shipped',
                $message,
                'shipped',
            );
            $this->telegramSendingService->sendOrderStatusUpdate($order->fresh(), $message);
        });
    }

    public function notifyCustomerDelivered(Order $order): void
    {
        $message = 'Your order has been delivered. Thank you for shopping with us.';
        $this->broadcastCustomerUpdate($order, 'delivered', 'Order delivered', $message);
        DB::afterCommit(function () use ($order, $message) {
            $this->beamsSendingService->sendOrderStatusUpdate(
                $order->fresh(),
                'Order delivered',
                $message,
                'delivered',
            );
            $this->telegramSendingService->sendOrderStatusUpdate($order->fresh(), $message);
        });
    }

    private function broadcastCustomerUpdate(Order $order, string $eventType, string $title, string $message): void
    {
        event(new CustomerOrderRealtimeAlert((int) $order->customer_id, [
            'kind' => 'customer.order_alert',
            'event_type' => $eventType,
            'title' => $title,
            'message' => $message,
            'order' => $this->buildOrderSnapshot($order, $eventType),
        ]));
    }

    private function buildAdminPayload(
        Order $order,
        string $eventType,
        string $title,
        string $message,
    ): array {
        return [
            'kind' => 'admin.order_alert',
            'event_type' => $eventType,
            'title' => $title,
            'message' => $message,
            'order' => $this->buildOrderSnapshot($order, $eventType),
        ];
    }

    private function buildOrderSnapshot(Order $order, string $eventType): array
    {
        $customer = $order->customer;
        $customerName = (string) (
            $customer?->full_name
            ?: $customer?->user_name
            ?: $customer?->email
            ?: 'Customer'
        );

        return [
            'id' => $order->id,
            'order_id' => '#' . $order->id,
            'customer_id' => $order->customer_id,
            'customer' => $customerName,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'total' => (float) $order->total_price,
            'created_at' => optional($order->created_at)->toISOString() ?: null,
            'event_type' => $eventType,
        ];
    }
}
