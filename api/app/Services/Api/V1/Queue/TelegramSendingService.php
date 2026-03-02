<?php

namespace App\Services\Api\V1\Queue;

use App\Models\Order;
use App\Notifications\CustomerOrderPaidTelegramNotification;
use Illuminate\Support\Facades\Notification;

class TelegramSendingService
{
    public function sendPaidOrderInvoice(Order $order): void
    {
        $order->loadMissing('customer:id,full_name,user_name,telegram_chat_id,telegram_user_id,telegram_username');

        $adminChatId = trim((string) config('services.telegram-bot-api.admin_chat_id', ''));
        if ($adminChatId !== '') {
            Notification::route('telegram', $adminChatId)
                ->notify(new CustomerOrderPaidTelegramNotification($order, true));
        }

        $customer = $order->customer;
        if (!$customer) {
            return;
        }

        $customerChatId = $customer->telegram_chat_id ?: $customer->telegram_user_id;
        if ($customerChatId) {
            $customer->notify(new CustomerOrderPaidTelegramNotification($order, false));
        }
    }
}
