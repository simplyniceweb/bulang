<?php

namespace App\Http\Controllers\Teller;

use App\Http\Controllers\Controller;
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
                return Inertia::render('Teller/NoActiveEvent');
            }

            $lastRound = Round::where('event_id', $event->id)
                ->latest('round_number')
                ->first();

            $rounds = Round::select('id', 'event_id', 'round_number', 'winner', 'status')
                        ->where('event_id', $event->id)
                        ->latest('round_number')
                        ->take(20)
                        ->get();

            $tellerData = $event->tellers()->where('user_id', auth()->id())->first();

            return Inertia::render('Teller/Dashboard', [
                'event' => $event,
                'round' => $lastRound,
                'rounds' => $rounds,
                'teller' => [
                    'id' => $tellerData->id,
                    'name' => $tellerData->name,
                    'initial' => $tellerData->pivot->initial_wallet ?? 0,
                    'current' => $tellerData->pivot->current_wallet ?? 0,
                ]
            ]);
        } catch (\Exception $e) {
                return Inertia::render('Teller/NoActiveEvent');
        }
    }
}
