<?php

namespace App\Events\Orders;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerOrderRealtimeAlert implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public bool $afterCommit = true;

    public function __construct(
        public int|string $customerId,
        public array $payload,
    ) {
    }

    //open channels
    public function broadcastOn(): array
    {
        return [new PrivateChannel('customers.' . $this->customerId)];
    }

    // broad
    public function broadcastAs(): string
    {
        return 'order.alert';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
