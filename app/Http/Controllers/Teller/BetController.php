<?php

namespace App\Http\Controllers\Teller;

use App\Events\BettingUpdated;
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
            // 1. Lock the round and get payout details FIRST
            $round = Round::where('id', $roundId)->lockForUpdate()->firstOrFail();

            // 2. Use cached totals instead of calling SUM() on the tickets table
            // Ensure you add these columns to your rounds table migration!
            if ($side === 'meron') {
                $round->total_meron += $amount;
            } elseif ($side === 'wala') {
                $round->total_wala += $amount;
            } else {
                $round->total_draw += $amount;
            }

            // 3. Calculation logic
            $housePercent = $round->house_percent ?? $event?->house_percent ?? 6;
            $plasada = $housePercent / 100;

            $totalPool = $round->total_meron + $round->total_wala;
            $netPool = $totalPool * (1 - $plasada);

            // Odds for THIS ticket (Current estimate)
            if ($side === 'draw') {
                $ticketOdds = 7.00;
            } else {
                $sideTotal = ($side === 'meron') ? $round->total_meron : $round->total_wala;
                $ticketOdds = ($sideTotal > 0) ? ($netPool / $sideTotal) : 0.94;
            }

            // 4. Create Ticket (One save call)
            $ticket = Ticket::create([
                'status'           => 'pending',
                'event_id'         => $event->id,
                'round_id'         => $round->id,
                'teller_id'        => auth()->id(),
                'side'             => $side,
                'amount'           => $amount,
                'odds'             => $ticketOdds,
                'potential_payout' => $amount * $ticketOdds,
                'ticket_number'    => 'PENDING'
            ]);

            // 5. Update Ticket Number and Round Totals/Odds in one go
            $oddMeron = ($round->total_meron > 0) ? ($netPool / $round->total_meron) * 100 : 0;
            $oddWala = ($round->total_wala > 0) ? ($netPool / $round->total_wala) * 100 : 0;

            $round->update([
                'total_meron' => $round->total_meron,
                'total_wala'  => $round->total_wala,
                'total_draw'  => $round->total_draw,
                'meron_odds'  => $oddMeron,
                'wala_odds'   => $oddWala,
            ]);

            $sideOdds = $side === 'meron' ? $oddMeron : $oddWala;

            $ticket->update([
                'ticket_number' => "{$event->id}-{$round->id}-{$ticket->id}-" . strtoupper(Str::random(4)),
                'odds' => $sideOdds,
                'potential_payout' => $ticket->amount * ($sideOdds / 100),
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

        $sideOdds = $round->{$ticket->side.'_odds'};
        $ticket->update([
            'odds' => $sideOdds,
            'potential_payout' => $round->status === 'cancelled' ? $ticket->amount : $ticket->amount * ($sideOdds / 100)
        ]);
        

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
                'status' => 'paid',
                'claimed_at' => now(),
                'paid_by' => auth()->id(), // Track which teller paid this out
            ]);

            return response()->json([
                'message' => 'Payout successful!',
                'ticket' => $ticket->fresh(['round', 'teller:id,name', 'paidBy:id,name']), // Return the updated ticket with relations
            ]);
        });
    }

    public function refund($ticketNumber)
    {
        return DB::transaction(function () use ($ticketNumber) {
            $ticket = Ticket::where('ticket_number', $ticketNumber)
                    ->where('claimed_at', null) // Only refund if not already settled
                    ->lockForUpdate()
                    ->firstOrFail();

            // 1. Update the bet status
            $ticket->update([
                'status' => 'refunded',
                'refunded_at' => now(),
                'refunded_by' => auth()->id(),
            ]);

            // 2. Refund the Teller/User Balance (if using a digital wallet)
            // auth()->user()->increment('balance', $bet->amount);

            // 3. Trigger Broadcast to update the UI / Odds / Total Bet Pool
            broadcast(new BettingUpdated($ticket->round));

            return response()->json(['message' => 'Ticket refunded successfully']);
        });
    }
}
