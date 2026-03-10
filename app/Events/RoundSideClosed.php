<?php
// app/Events/RoundSideClosed.php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoundSideClosed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $round;
    public $side;
    public $closed_at;

    /**
     * Create a new event instance.
     */
    public function __construct($round, $side)
    {
        $this->round = $round;
        $this->side = $side; // 'meron', 'wala', or 'draw'
        $this->closed_at = now()->toDateTimeString();
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        return new Channel('rounds');
    }

    public function broadcastAs(): string
    {
        return 'round.side.closed';
    }

    public function broadcastWith(): array
    {
        return [
            'round' => $this->round,
            'side' => $this->side,
            'closed_at' => $this->closed_at
        ];
    }
}