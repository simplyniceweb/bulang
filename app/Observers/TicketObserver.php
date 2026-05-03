<?php

namespace App\Observers;

use App\Events\BettingUpdated;
use App\Models\Ticket;

class TicketObserver
{
    /**
     * Handle the Ticket "created" event.
     */
    public function created(Ticket $ticket): void
    {
    }

    public function saved(Ticket $ticket): void
    {
        if (!$ticket->wasChanged('status') && !$ticket->wasRecentlyCreated) {
            return;
        }
        
        // 'saved' fires after BOTH created and updated
        $round = $ticket->round;
        if ($round) {
            broadcast(new BettingUpdated($round));
        }
    }

    /**
     * Handle the Ticket "updated" event.
     */
    public function updated(Ticket $ticket): void
    {
        //
    }

    /**
     * Handle the Ticket "deleted" event.
     */
    public function deleted(Ticket $ticket): void
    {
        //
    }

    /**
     * Handle the Ticket "restored" event.
     */
    public function restored(Ticket $ticket): void
    {
        //
    }

    /**
     * Handle the Ticket "force deleted" event.
     */
    public function forceDeleted(Ticket $ticket): void
    {
        //
    }
}
