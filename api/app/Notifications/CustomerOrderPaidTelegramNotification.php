<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

class CustomerOrderPaidTelegramNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
        private readonly bool $isAdminRecipient = false,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['telegram'];
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        $frontendBase = rtrim((string) config('app.frontend_url', config('app.url')), '/');
        $viewUrl = $frontendBase . '/orders/invoices/' . $this->order->id;
        $customerName = (string) ($this->order->customer?->full_name ?: $this->order->customer?->user_name ?: 'Customer');
        $telegramUserId = (string) ($this->order->customer?->telegram_user_id ?? '-');
        $telegramUsername = (string) ($this->order->customer?->telegram_username ?? '-');

        $header = $this->isAdminRecipient
            ? "New paid order: #{$this->order->id}"
            : "Payment confirmed for order #{$this->order->id}";

        return TelegramMessage::create()
            ->content($header)
            ->line("Customer: {$customerName}")
            ->line("Telegram ID: {$telegramUserId}")
            ->line("Telegram Username: {$telegramUsername}")
            ->line('Total: $' . number_format((float) $this->order->total_price, 2))
            ->line('Payment status: *PAID*')
            ->button('View Invoice', $viewUrl);
    }
}
