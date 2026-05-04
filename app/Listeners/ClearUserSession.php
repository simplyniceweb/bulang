<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;

class ClearUserSession
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        if ($event->user) {
            $event->user->session_id = null;
            $event->user->last_activity = null;
            $event->user->save();
        }
    }
}
