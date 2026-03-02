<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoundCancelled implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $round;
    public $reason;

    /**
     * Create a new event instance.
     */
    public function __construct($round, $reason = null)
    {
        $this->round = $round;
        $this->reason = $reason;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): Channel
    {
        return new Channel('rounds');
    }

    public function broadcastAs(): string
    {
        return 'round.cancelled';
    }

    public function broadcastWith(): array
    {
        return [
            'round' => $this->round,
            'event' => 'cancelled',
            'reason' => $this->reason,
            'cancelled_at' => now()->toDateTimeString()
        ];
    }
}
