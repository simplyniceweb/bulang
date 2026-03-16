<?php

namespace App\Http\Controllers\Teller;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Round;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BetController extends Controller
{
    public function bet(Event $event, Request $request)
    {
        $request->validate([
            'side' => 'required|in:meron,wala,draw',
            'amount' => 'required|numeric|min:100',
            'round_id' => 'required|numeric',
        ]);

        $side = $request->input('side');
        $amount = $request->input('amount');
        $roundId = $request->input('round_id');

        if ($event->betting_closed) {
            return response()->json([
                'message' => 'Betting is closed for this event.',
            ], 403);
        }

        $ticket = DB::transaction(function () use ($event, $roundId, $side, $amount) {            
            $ticket = new Ticket();
            $ticket->ticket_number = 'TEMP-' . Str::random(10);
            $ticket->event_id = $event->id;
            $ticket->round_id = $roundId;
            $ticket->teller_id = auth()->id();
            $ticket->side = $side;
            $ticket->amount = $amount;
            $ticket->odds = 1;
            $ticket->potential_payout = 0;
            $ticket->save();

            // recalculate odds
            $round = Round::where('id', $roundId)->lockForUpdate()->firstOrFail();
            $payouts = $round->payout_details;

            $currentOdds = ($side === 'meron') ? $payouts['meron_payout'] : $payouts['wala_payout'];
            if ($side === 'draw') {
                $currentOdds = $payouts['draw_multiplier'];
            }

            $ticketOdds = $currentOdds / 100;
            
            $ticket->update([
                'ticket_number' => $event->id. '-' . $ticket->round_id . '-' . $ticket->id . '-' . strtoupper(Str::random(4)),
                'odds' => $ticketOdds, // Current odds at time of bet
                'potential_payout' => $ticket->amount * $ticketOdds
            ]);

            return $ticket;
        });

        return back()->with('newTicket', [
            'ticket_number' => $ticket->ticket_number,
            'round_id' => $ticket->round_id,
            'side' => $ticket->side,
            'amount' => number_format($ticket->amount, 2),
            'potential_payout' => number_format($ticket->potential_payout, 2),
            'odds' => $ticket->odds,
            'teller_name' => auth()->user()->name,
            'date' => now()->format('M d, Y h:i A'),
            'message' => "Bet of ₱{$ticket->amount} on {$ticket->side} confirmed.",
        ]);
    }

    public function verify($ticketNumber)
    {
        // block claim if event is halted
        $event = Event::cachedActive();
        if ($event->halt_event) {
            return response()->json(['message' => 'Event halted for redeclaration of winner.'], 422);
        }

        // Retrieve ticket with round data
        $ticket = Ticket::with('round')->where('ticket_number', $ticketNumber)->first();

        if (!$ticket) {
            return response()->json(['message' => 'Ticket Not Found'], 422);
        }

        // for testing purposes only - reset claimed status
        // $ticket->update([
        //     'claimed_at' => null,
        //     'paid_by' => null,
        // ]);

        $round = $ticket->round;
        $status = 'pending'; 
        $can_payout = false;
        $payout_amount = 0;

        // 1. Check if already claimed
        if ($ticket->claimed_at) {
            return response()->json([
                'ticket' => $ticket,
                'status' => 'already_paid',
                'can_payout' => false,
                'message' => 'This ticket was already paid at ' . $ticket->claimed_at->format('Y-m-d h:i A')
            ]);
        }

        // 2. Evaluate based on Round Status
        switch ($round->status) {
            case 'cancelled':
                $status = 'cancelled';
                $can_payout = true; 
                $payout_amount = $ticket->amount; // Refund the original bet
                break;

            case 'closed':
                if ($round->winner === $ticket->side) {
                    $status = 'winner';
                    $can_payout = true;
                    $payout_amount = $ticket->potential_payout;
                } elseif ($round->winner === null) {
                    $status = 'waiting_result'; // Fight over, but result not encoded yet
                    $can_payout = false;
                } else {
                    $status = 'loser';
                    $can_payout = false;
                }
                break;

            case 'open':
                $status = 'betting_open'; // Still live, cannot pay yet
                $can_payout = false;
                break;
        }

        return response()->json([
            'ticket' => $ticket,
            'status' => $status,
            'can_payout' => $can_payout,
            'payout_amount' => $payout_amount
        ]);
    }

    public function claim($ticketNumber)
    {
        return DB::transaction(function () use ($ticketNumber) {
            // Lock the ticket row for update to prevent race conditions
            $ticket = Ticket::with(['round', 'teller:id,name', 'paidBy:id,name'])
                ->where('ticket_number', $ticketNumber)
                ->lockForUpdate()
                ->first();

            if (!$ticket) {
                return response()->json(['message' => 'Ticket not found'], 404);
            }

            if ($ticket->claimed_at) {
                return response()->json(['message' => 'Ticket already claimed'], 422);
            }

            // Logic Check: Ensure the round is actually closed or cancelled
            $round = $ticket->round;
            if ($round->status === 'open') {
                return response()->json(['message' => 'Round is still open'], 422);
            }

            // Process Claim
            $ticket->update([
                'claimed_at' => now(),
                'paid_by' => auth()->id(), // Track which teller paid this out
            ]);

            return response()->json([
                'message' => 'Payout successful!',
                'ticket' => $ticket->fresh(['round', 'teller:id,name', 'paidBy:id,name']), // Return the updated ticket with relations
            ]);
        });
    }
}
