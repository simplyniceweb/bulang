<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoundOpened implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $round;

    /**
     * Create a new event instance.
     */
    public function __construct($round)
    {
        $this->round = $round;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        return new Channel('rounds');
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'round.opened';
    }

    public function broadcastWith()
    {
        return [
            'round' => $this->round,
            'event' => 'opened',
            'timestamp' => now()->toDateTimeString()
        ];
    }
}