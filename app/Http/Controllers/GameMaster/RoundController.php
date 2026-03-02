<?php

namespace App\Http\Controllers\GameMaster;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Round;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RoundController extends Controller
{
    public function open()
    {
        $event = Event::cachedActive();

        // Prevent multiple open rounds
        if (Round::where('event_id', $event->id)
            ->where('status', 'open')
            ->exists()) {

            return back()->withErrors([
                'round' => 'There is already an open round. Declare a winner or cancel this round before opening a new one.'
            ]);
        }

        $lastRound = Round::where('event_id', $event->id)
            ->latest('round_number')
            ->first();

        $roundNumber = $lastRound ? $lastRound->round_number + 1 : 1;

        DB::transaction(function () use ($event, $roundNumber) {

            Round::create([
                'event_id' => $event->id,
                'round_number' => $roundNumber,
                'status' => 'open',
                'opened_at' => now(),
            ]);

        });

        return back()->with('success', 'Round opened successfully.');
    }

    public function closeBet(Round $round, Request $request)
    {
        $side = $request->side;

        if ($side === 'wala' && $round->total_wala <= $round->total_meron) {
            return back()->withErrors(['wala' => 'Wala must be higher to close']);
        }

        if ($side === 'meron' && $round->total_meron <= $round->total_wala) {
            return back()->withErrors(['meron' => 'Meron must be higher to close']);
        }

        $round->update([
            "{$side}_closed" => true
        ]);

        return back();
    }

    public function declareWinner(Round $round, Request $request)
    {
        $winner = $request->winner;

        $round->update([
            'status' => 'closed',
            'winner' => $winner,
            'closed_at' => now(),
        ]);

        Cache::forget('active_event');

        return back();
    }

    public function cancel(Round $round)
    {
        $round->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        Cache::forget('active_event');

        return back();
    }
}
