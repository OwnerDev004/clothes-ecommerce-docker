<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    // braodcastWith

    public function broadcastWith()
    {
        return ['message' => $this->message];
    }

    /**
     * Define the channel(s) the event should broadcast on.
     */
    public function broadcastOn()
    {
        return new Channel('test-channel');
    }
}
