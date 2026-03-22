<?php

namespace App\Http\Controllers\GameMaster;

use App\Events\RoundCancelled;
use App\Events\RoundClosed;
use App\Events\RoundDeclare;
use App\Events\RoundOpened;
use App\Events\RoundSideClosed;
use App\Events\RoundSideReopened;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Round;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RoundController extends Controller
{
    public function open(Request $request)
    {
        $event = Event::cachedActive();
        $noModal = $request->noModal ?? false;

        // Check for any round that isn't fully "finished"
        // A round is unfinished if it is 'open' OR if it's 'closed' but has no winner/result.
        $unfinishedRound = Round::where('event_id', $event->id)
            ->where(function ($query) {
                $query->where('status', 'open')
                        ->orWhere(function ($q) {
                            $q->where('status', 'closed')
                            ->where('betting_closed', true)
                            ->whereNull('winner');
                        });
            })
            ->first();

        if ($unfinishedRound) {
            if ($unfinishedRound->status === 'open') {
                $message = "Round #{$unfinishedRound->round_number} is still open.";
            } elseif ($unfinishedRound->betting_closed && is_null($unfinishedRound->winner)) {
                $message = "Round #{$unfinishedRound->round_number} is waiting for a winner to be declared.";
            } else {
                $message = "The previous round is not yet finalized.";
            }

            return back()->withErrors([
                'round' => $message . ' Please resolve it before opening a new one.',
            ]);
        }

        $lastRound = Round::where('event_id', $event->id)
            ->latest('round_number')
            ->first();

        $roundNumber = $lastRound ? $lastRound->round_number + 1 : 1;

        $percent = $request->house_percent ?? $event->house_percent;
        $newRound = DB::transaction(function () use ($event, $roundNumber, $percent) {

            return Round::create([
                'event_id' => $event->id,
                'round_number' => $roundNumber,
                'status' => 'open',
                'house_percent' => $percent,
                'opened_at' => now(),
            ]);

        });

        broadcast(new RoundOpened($newRound));

        return back()->with([
            'success' => 'Round opened successfully.', 
            'round' => $lastRound,
            'round_number' => $roundNumber,
            'round_status' => 'open',
            'no_modal' => $noModal
        ]);
    }

    public function declareWinner(Round $round, Request $request)
    {
        $winner = $request->winner; // 'meron' | 'wala' | 'draw'
        $noModal = $request->noModal ?? false;

        $percentage = $round->house_percent ?? $event?->house_percent ?? 6;
        $totalPool = $round->bet_sum;

        $houseCut = ($totalPool * $percentage) / 100;

        // redeclare??
        $event = Event::cachedActive();
        if ($event->halt_event) {
            $round->update([
                'winner' => $winner,
                'closed_at' => now(),
                'house_cut' => $houseCut,
            ]);

            $event->update([
                'halt_event' => false,
            ]);

            Cache::forget('active_event');
            broadcast(new RoundDeclare($round));

            return back()->with([
                'success' => "Winner for round #{$round->round_number} is ".strtoupper($winner).".",
                'round_number' => $round->round_number,
                'round_status' => 'closed',
                'no_modal' => $noModal
            ]);
        }

        if ($round->status === 'cancelled' || $round->status === 'open') {
            return back()->withErrors(['round' => 'Cannot declare winner for a cancelled or open round.']);
        }

        if ($round->status === 'closed' && !$round->betting_closed) {
            return back()->withErrors(['round' => 'Cannot declare winner: betting is not closed yet. Please close betting first.']);
        }

        if ($round->winner !== null) {
            return back()->withErrors(['round' => "Winner has already been declared for this round #{$round->round_number} and it's ".strtoupper($round->winner)."."]);
        }

        $round->update([
            'status' => 'closed',
            'winner' => $winner,
            'closed_at' => now(),
            'house_cut' => $houseCut
        ]);

        Cache::forget('active_event');
        broadcast(new RoundDeclare($round));

        return back()->with([
            'success' => "Winner for round #{$round->round_number} is ".strtoupper($winner).".",
            'round_number' => $round->round_number,
            'round_status' => 'closed',
            'no_modal' => $noModal
        ]);
    }

    public function cancel(Round $round, Request $request)
    {
        $noModal = $request->noModal ?? false;

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
        broadcast(new RoundCancelled($round, 'Cancelled by Game Master'));

        return back()->with([
            'success' => 'Round cancelled successfully.',
            'round_number' => $round->round_number,
            'round_status' => 'cancelled',
            'no_modal' => $noModal
        ]);
    }

    public function closeSide(Round $round, Request $request)
    {
        $side = $request->side; // 'meron' | 'wala' | 'draw'
        $reopen = $request->reopen ?? false;
        $noModal = $request->noModal ?? false;

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
        
        broadcast($reopen 
            ? new RoundSideReopened($round, $side) 
            : new RoundSideClosed($round, $side)
        );

        return back()->with([
            'success' => strtoupper($side).' betting is now '.strtoupper($reopen ? 'open' : 'closed').'.',
            'round_number' => $round->round_number,
            'round_status' => $round->status,
            'no_modal' => $noModal
        ]);
    }

    public function closeGlobalBetting(Round $round, Request $request)
    {
        $noModal = $request->noModal ?? false;

        if ($round->status !== 'open') {
            return back()->withErrors(['round' => 'Global betting already closed or round cancelled.']);
        }

        DB::transaction(function () use ($round) {
            $round->update([
                'status' => 'closed',
                'betting_closed' => true,
                'closed_at' => now(),
            ]);

            // 1. Get the final, frozen payout multipliers
            $payouts = $round->payout_details; 
            $meronMultiplier = $payouts['meron_payout'] / 100;
            $walaMultiplier = $payouts['wala_payout'] / 100;
            $drawMultiplier = $payouts['draw_multiplier'];

            // 2. Bulk update all tickets for this round so they are "Locked"
            // This is much more efficient than updating one by one.
            DB::statement("
                UPDATE tickets 
                SET odds = CASE 
                    WHEN side = 'meron' THEN ? 
                    WHEN side = 'wala' THEN ? 
                    ELSE ? 
                END,
                potential_payout = amount * (CASE 
                    WHEN side = 'meron' THEN ? 
                    WHEN side = 'wala' THEN ? 
                    ELSE ? 
                END)
                WHERE round_id = ?
            ", [
                $meronMultiplier, $walaMultiplier, $drawMultiplier,
                $meronMultiplier, $walaMultiplier, $drawMultiplier,
                $round->id
            ]);
        });
        
        broadcast(new RoundClosed($round));

        return back()->with([
            'success' => 'Global betting closed. You can now declare the winner.',
            'round_number' => $round->round_number,
            'no_modal' => $noModal
        ]);
    }
}
