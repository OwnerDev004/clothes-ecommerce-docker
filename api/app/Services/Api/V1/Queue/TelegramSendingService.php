<?php

namespace App\Services\Api\V1\Queue;

use App\Models\Order;
use App\Notifications\CustomerOrderPaidTelegramNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

class TelegramSendingService
{
    public function sendPaidOrderInvoice(Order $order): void
    {
        $order->loadMissing('customer:id,full_name,user_name,telegram_chat_id,telegram_user_id,telegram_username,enable_telegram_alerts');

        $adminChatId = trim((string) config('services.telegram-bot-api.admin_chat_id', ''));
        if ($adminChatId !== '') {
            try {
                Notification::route('telegram', $adminChatId)
                    ->notify(new CustomerOrderPaidTelegramNotification($order, true));
            } catch (\Throwable $e) {
                $this->logDelivery('admin_failed', $order->id, null, $adminChatId);
                $this->sendDirectMessage($adminChatId, $this->buildFallbackText($order, true));
            }
        }

        $customer = $order->customer;
        if (!$customer) {
            $this->logDelivery('skip_missing_customer', $order->id);
            return;
        }

        if (!$customer->enable_telegram_alerts) {
            $this->logDelivery('skip_alerts_disabled', $order->id, $customer->id);
            return;
        }

        $customerChatId = $customer->routeNotificationForTelegram();
        if ($customerChatId) {
            try {
                $customer->notify(new CustomerOrderPaidTelegramNotification($order, false));
                $this->logDelivery('sent', $order->id, $customer->id, $customerChatId);
            } catch (\Throwable $e) {
                $this->logDelivery('send_failed', $order->id, $customer->id, $customerChatId);
                $this->sendDirectMessage($customerChatId, $this->buildFallbackText($order, false));
            }
        } else {
            $this->logDelivery('skip_missing_chat_id', $order->id, $customer->id);
        }
    }

    private function buildFallbackText(Order $order, bool $isAdminRecipient): string
    {
        $customerName = (string) ($order->customer?->full_name ?: $order->customer?->user_name ?: 'Customer');
        $header = $isAdminRecipient
            ? "New paid order: #{$order->id}"
            : "Payment confirmed for order #{$order->id}";
        $invoiceLink = $this->buildLinkInvoice($order);

        return implode("\n", [
            $header,
            "Customer: {$customerName}",
            'Total: $' . number_format((float) $order->total_price, 2),
            'Payment status: PAID',
            $invoiceLink ? 'Invoice: ' . url($invoiceLink) : 'Invoice: unavailable',
        ]);
    }
    private function buildLinkInvoice(Order $order): ?string
    {
        $frontendBase = rtrim((string) config('app.frontend_url', config('app.url', 'http://localhost:3000')), '/');
        if ($frontendBase === '') {
            return null;
        }
        return $frontendBase . '/orders/invoices/' . $order->id;
    }

    private function sendDirectMessage(string $chatId, string $text): void
    {
        $botToken = trim((string) config('services.telegram-bot-api.token', ''));
        if ($chatId === '' || $botToken === '') {
            $this->logDelivery('missing_token', 0, null, $chatId);
            return;
        }

        $baseUri = rtrim((string) config('services.telegram-bot-api.base_uri', 'https://api.telegram.org'), '/');
        try {
            Http::timeout(10)->post("{$baseUri}/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'Markdown',
            ]);
        } catch (\Throwable $e) {
            Log::warning('telegram.direct_send_failed', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function logDelivery(string $status, int $orderId, ?int $customerId = null, ?string $chatId = null): void
    {
        if (!config('services.telegram-bot-api.log_delivery')) {
            return;
        }

        Log::info('telegram.delivery', [
            'status' => $status,
            'order_id' => $orderId,
            'customer_id' => $customerId,
            'chat_id' => $chatId,
        ]);
    }
}
