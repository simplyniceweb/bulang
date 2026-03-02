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
                'round' => 'There is already an open round. Declare a winner or cancel this round before opening a new one.',
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

        return back()->with([
            'success' => 'Round opened successfully.', 
            'round' => $lastRound,
            'round_number' => $roundNumber,
            'round_status' => 'open',
        ]);
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
        if ($round->status === 'cancelled') {
            return back()->withErrors(['round' => 'Cannot declare winner for a cancelled round.']);
        }

        if ($round->status === 'closed' && !$round->betting_closed) {
            return back()->withErrors(['round' => 'Cannot declare winner: betting is not closed yet. Please close betting first.']);
        }

        if ($round->winner !== null) {
            return back()->withErrors(['round' => "Winner has already been declared for this round #{$round->round_number} and it's ".strtoupper($round->winner)."."]);
        }
        
        $winner = $request->winner;

        $round->update([
            'status' => 'closed',
            'winner' => $winner,
            'closed_at' => now(),
        ]);

        Cache::forget('active_event');

        return back()->with([
            'success' => 'Winner declared successfully.',
            'round_number' => $round->round_number,
            'round_status' => 'closed',
        ]);
    }

    public function cancel(Round $round)
    {
        // Prevent recancelling round or cancelling after winner declared
        if ($round->status !== 'open' || $round->winner !== null) {
            return back()->withErrors([
                'round' => 'Cannot cancel this round: either it is already closed, cancelled, or winner has been declared.'
            ]);
        }

        $round->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        Cache::forget('active_event');

        return back()->with([
            'success' => 'Round cancelled successfully.',
            'round_number' => $round->round_number,
            'round_status' => 'cancelled',
        ]);
    }

    public function closeSide(Round $round, Request $request)
    {
        $side = $request->side; // 'meron' | 'wala' | 'draw'
        $reopen = $request->reopen ?? false;

        if (!in_array($side, ['meron','wala','draw'])) {
            return back()->withErrors([
                'round' => 'Invalid side selected.'
            ]);
        }

        // Prevent closing a side if round is already closed or winner declared
        if ($round->status !== 'open' || $round->winner !== null) {
            return back()->withErrors([
                'round' => 'Cannot close side: round is already closed or winner declared. Please open a new round.'
            ]);
        }

        // Prevent closing if side already closed
        $column = $side.'_closed';
    
        // Update round
        $round->update([
            $column => !$reopen ? true : false
        ]);

        return back()->with([
            'success' => ucfirst($side).' betting is now '.($reopen ? 'open' : 'closed').'.',
            'round_number' => $round->round_number,
            'round_status' => $round->status,
        ]);
    }

    public function closeGlobalBetting(Round $round)
    {
        if ($round->status !== 'open') {
            return back()->withErrors(['round' => 'Global betting already closed or round cancelled.']);
        }

        $round->update([
            'status' => 'closed',
            'betting_closed' => true,
            'closed_at' => now(),
        ]);

        return back()->with([
            'success' => 'Global betting closed. You can now declare the winner.',
            'round_number' => $round->round_number,
        ]);
    }
}
