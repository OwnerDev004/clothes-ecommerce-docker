<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\Api\V1\Queue\TelegramSendingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendingInvoiceTelegramJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(private readonly int $orderId)
    {
    }

    public function handle(TelegramSendingService $telegramSendingService): void
    {
        $order = Order::query()
            ->with('customer:id,full_name,user_name,telegram_chat_id,telegram_user_id,telegram_username')
            ->find($this->orderId);

        if (!$order) {
            return;
        }

        \Log::info($order);

        $telegramSendingService->sendPaidOrderInvoice($order);
    }
}
