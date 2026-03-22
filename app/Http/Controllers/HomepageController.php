<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Round;
use Inertia\Inertia;
use Laravel\Fortify\Features;

class HomepageController extends Controller
{
    public function index()
    {
        try {
            $event = Event::cachedActive();
            if (!$event) {
                return Inertia::render('Teller/NoActiveEvent');
            }

            $lastRound = Round::where('event_id', $event->id)
                ->with('event') // Preload event for the accessor
                ->withSum(['tickets as meron_sum' => fn($q) => $q->where('side', 'meron')->where('status', 'pending')], 'amount')
                ->withSum(['tickets as wala_sum' => fn($q) => $q->where('side', 'wala')->where('status', 'pending')], 'amount')
                ->withSum(['tickets as draw_sum' => fn($q) => $q->where('side', 'draw')->where('status', 'pending')], 'amount')
                ->latest('round_number')
                ->first();

            $rounds = Round::select('id', 'event_id', 'round_number', 'winner', 'status')
                        ->where('event_id', $event->id)
                        ->where('status', '!=', 'open')
                        ->latest('round_number')
                        ->take(4)
                        ->get();

            $stats = Round::where('event_id', $event->id)
                ->selectRaw("
                    COUNT(CASE WHEN winner = 'meron' THEN 1 END) as meron_count,
                    COUNT(CASE WHEN winner = 'wala' THEN 1 END) as wala_count,
                    COUNT(CASE WHEN winner = 'draw' THEN 1 END) as draw_count,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_count
                ")
                ->first();

            return Inertia::render('Welcome', [
                'event' => $event,
                'round' => $lastRound,
                'rounds' => $rounds,
                'stats' => $stats,
                'canRegister' => Features::enabled(Features::registration()),
            ]);
        } catch (\Exception $e) {
            return Inertia::render('Teller/NoActiveEvent');
        }
    }
}
