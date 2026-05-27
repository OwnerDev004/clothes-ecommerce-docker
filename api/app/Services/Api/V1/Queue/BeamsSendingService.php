<?php

namespace App\Services\Api\V1\Queue;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Pusher\PushNotifications\PushNotifications;

class BeamsSendingService
{
    public function sendOrderStatusUpdate(Order $order, string $title, string $message, string $eventType): void
    {
        $instanceId = trim((string) config('services.beams.instance_id', ''));
        $secretKey = trim((string) config('services.beams.secret_key', ''));
        $customerId = trim((string) ($order->customer_id ?? ''));

        if ($instanceId === '' || $secretKey === '' || $customerId === '') {
            return;
        }

        $frontendBase = rtrim((string) config('app.frontend_url', config('app.url', 'http://localhost:3000')), '/');
        $deepLink = $frontendBase !== ''
            ? $frontendBase . '/orders/invoices/' . $order->id
            : null;

        $beamsClient = new PushNotifications([
            'instanceId' => $instanceId,
            'secretKey' => $secretKey,
        ]);

        try {
            $beamsClient->publishToUsers([
                (string) $customerId,
            ], [
                'web' => [
                    'notification' => [
                        'title' => $title,
                        'body' => $message,
                        'deep_link' => $deepLink ?: ($frontendBase ?: '/'),
                    ],
                ],
                'data' => [
                    'kind' => 'customer.order_alert',
                    'event_type' => $eventType,
                    'order_id' => $order->id,
                    'deep_link' => $deepLink,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('beams.publish_failed', [
                'order_id' => $order->id,
                'customer_id' => $customerId,
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
