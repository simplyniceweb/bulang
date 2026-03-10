<?php
// app/Events/RoundSideReopened.php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class RoundSideReopened implements ShouldBroadcastNow
{
    use Dispatchable;

    public $round;
    public $side;

    public function __construct($round, $side)
    {
        $this->round = $round;
        $this->side = $side;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('rounds');
    }

    public function broadcastAs(): string
    {
        return 'round.side.reopened';
    }

    public function broadcastWith(): array
    {
        return [
            'round' => $this->round,
            'side' => $this->side
        ];
    }
}