<?php

namespace App\Http\Controllers\GameMaster;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Round;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

            $rounds = Round::select('id', 'round_number', 'winner', 'status')
                        ->where('event_id', $event->id)
                        ->latest('round_number')
                        ->take(20)
                        ->get();

            return Inertia::render('GameMaster/Dashboard', [
                'event' => $event,
                'round' => $lastRound,
                'rounds' => $rounds,
            ]);
        } catch (\Exception $e) {
                return Inertia::render('GameMaster/NoActiveEvent');
        }
    }

    public function haltEvent(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $event = Event::where('id', Event::cachedActive()->id)->lockForUpdate()->first();

            if (!$event) {
                return redirect()->back()->with('error', "No event found.");
            }

            $halt = $request->boolean('halt');

            $event->update([
                'halt_event' => $halt,
                'halt_count' => $halt ? ($event->halt_count + 1) : $event->halt_count,
            ]);

            Cache::forget('active_event');

            $status = $event->halt_event ? 'Halted' : 'Resumed';
            return redirect()->back()->with('success', "Event status: " . ($halt ? 'Halted' : 'Resumed'));
        });
    }
}
