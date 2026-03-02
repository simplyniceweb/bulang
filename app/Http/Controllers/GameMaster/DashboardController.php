<?php

namespace App\Http\Controllers\GameMaster;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Round;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            $event = Event::cachedActive();
            if (!$event) {
                return Inertia::render('GameMaster/NoActiveEvent');
            }

            $lastRound = Round::where('event_id', $event->id)
                ->latest('round_number')
                ->first();

            return Inertia::render('GameMaster/Dashboard', [
                'event' => $event,
                'round' => $lastRound,
                'round_id' => $lastRound ? $lastRound->id : null,
                'round_number' => $lastRound ? $lastRound->round_number : 0,
                'round_status' => $lastRound ? $lastRound->status : 'closed',
            ]);
        } catch (\Exception $e) {
                return Inertia::render('GameMaster/NoActiveEvent');
        }
    }
}
