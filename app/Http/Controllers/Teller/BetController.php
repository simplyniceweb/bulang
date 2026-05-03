<?php

namespace App\Http\Controllers\Teller;

use App\Enums\Bulang;
use App\Events\BettingUpdated;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Round;
use App\Models\Ticket;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Milon\Barcode\DNS1D;

class BetController extends Controller
{
    public function bet(Event $event, Request $request)
    {
        $request->validate([
            'side' => 'required|in:meron,wala,draw',
            'amount' => 'required|numeric|min:100',
            'round_id' => 'required|exists:rounds,id',
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
            // Lock the round and get payout details FIRST
            $round = Round::where('id', $roundId)
                ->where('event_id', $event->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Use cached totals instead of calling SUM() on the tickets table
            // Ensure you add these columns to your rounds table migration!
            $column = match ($side) {
                'meron' => 'total_meron',
                'wala' => 'total_wala',
                'draw' => 'total_draw',
            };

            $round->increment($column, $amount);
            $round->refresh();

            $userId = Auth::id();
            $divider = (int) Bulang::DIVIDER->value;
            $defaultOdds = (float) Bulang::DEFAULTODDS->value;
            $housePercent = (int) Bulang::HOUSEPERCENT->value;
            $drawMultiplier = (float) Bulang::DRAWMULTIPLIER->value;

            // Calculation logic
            $housePercent = $round->house_percent ?? $event?->house_percent ?? $housePercent;
            $plasada = $housePercent / $divider;

            $totalPool = $round->total_meron + $round->total_wala;
            $netPool = $totalPool * (1 - $plasada);

            // Odds and potential payout for THIS ticket (Current estimate)
            $oddMeron = ($round->total_meron > 0) ? ($netPool / $round->total_meron) : $defaultOdds;
            $oddWala = ($round->total_wala > 0) ? ($netPool / $round->total_wala) : $defaultOdds;
            $ticketOdds = $side === 'draw'
                ? $drawMultiplier
                : ($side === 'meron' ? $oddMeron : $oddWala);

            $potentialPayout = $amount * $ticketOdds;

            // Create Ticket (One save call)
            $ticket = Ticket::create([
                'status'           => 'pending',
                'event_id'         => $event->id,
                'round_id'         => $round->id,
                'teller_id'        => $userId,
                'side'             => $side,
                'amount'           => $amount,
                'odds'             => $ticketOdds,
                'potential_payout' => $potentialPayout,
                'ticket_number'    => $this->generateUniqueTicketNumber()
            ]);

            // Update Ticket Number and Round
            $round->update([
                'meron_odds'  => $oddMeron,
                'wala_odds'   => $oddWala,
            ]);

            // Update teller wallet
            $amount = (float) $amount;
            $event->tellers()->updateExistingPivot($userId, [
                'current_wallet' => DB::raw("current_wallet + {$amount}")
            ]);

            // create transaction
            app(TransactionService::class)->bet($ticket);

            $ticket->round_number = $round->round_number;

            return $ticket;
        });

        // broadcast(new BettingUpdated($ticket->round));

        $d = new DNS1D();

        return back()->with('newTicket', [
            'ticket_number' => $ticket->ticket_number,
            'round_id' => $ticket->round_number,
            'side' => $ticket->side,
            'amount' => number_format($ticket->amount, 2),
            'status' => $ticket->status,
            'potential_payout' => number_format($ticket->potential_payout, 2),
            'odds' => $ticket->odds,
            'teller_name' => Auth::user()?->name,
            'date' => now()->format('M d, Y h:i A'),
            'barcode_html' => $d->getBarcodeSVG($ticket->ticket_number, 'C128', 2, 45),
            'message' => "Bet of ₱{$ticket->amount} on {$ticket->side} confirmed.",
        ]);
    }

    public function verify(?string $ticketNumber)
    {
        // block claim if event is halted
        $event = Event::cachedActive();
        if ($event->halt_event) {
            return response()->json([
                'message' => 'Event halted for redeclaration of winner.'
            ], 422);
        }

        // Retrieve ticket with round data
        $ticket = Ticket::with('round')
            ->where('ticket_number', $ticketNumber)
            ->first();

        if (!$ticket) {
            return response()->json([
                'message' => 'Ticket Not Found'
            ], 422);
        }

        $status = 'pending'; 
        $can_payout = false;
        $payout_amount = 0;
        $round = $ticket->round;

        // 1. Check if already claimed
        if ($ticket->claimed_at) {
            return response()->json([
                'ticket' => $ticket,
                'status' => 'already_paid',
                'can_payout' => false,
                'message' => 'This ticket was already paid at ' . $ticket->claimed_at->format('Y-m-d h:i A')
            ]);
        }

        if ($ticket->refunded_at) {
            return response()->json([
                'ticket' => $ticket,
                'status' => 'refunded',
                'can_payout' => false,
                'message' => 'This ticket was already refunded at ' . $ticket->refunded_at->format('Y-m-d h:i A')
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
                dd($round->winner, $ticket->side);
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

    public function claim(?string $ticketNumber)
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

            $userId = Auth::id();

            // Process Claim
            $ticket->update([
                'status' => 'paid',
                'claimed_at' => now(),
                'paid_by' => $userId, // Track which teller paid this out
            ]);

            $sideOdds = $round->{$ticket->side.'_odds'};
            $multiplier = (float) Bulang::DRAWMULTIPLIER->value;
            $divider = (integer) Bulang::DIVIDER->value;
            $amount = $ticket->side !== 'draw' ? $ticket->amount * ($sideOdds / $divider) : $ticket->amount * $multiplier;

            // Update teller wallet
            $event = Event::where('id', Event::cachedActive()->id)->lockForUpdate()->first();
            $event->tellers()->updateExistingPivot($userId, [
                'current_wallet' => DB::raw('current_wallet - ' . (float)$amount)
            ]);

            app(TransactionService::class)->payout($ticket);

            return response()->json([
                'message' => 'Payout successful!',
                'ticket' => $ticket->fresh(['round', 'teller:id,name', 'paidBy:id,name']), // Return the updated ticket with relations
            ]);
        });
    }

    public function refund(?string $ticketNumber)
    {
        return DB::transaction(function () use ($ticketNumber) {
            $ticket = Ticket::where('ticket_number', $ticketNumber)
                    ->where('claimed_at', null) // Only refund if not already settled
                    ->lockForUpdate()
                    ->firstOrFail();

            // check if ticket does exist
            if (!$ticket) {
                return response()->json([
                    'message' => 'Ticket not found or already claimed'
                ], 404);
            }

            // check if ticket already refunded
            if ($ticket->refunded_at) {
                return response()->json([
                    'message' => 'Ticket already refunded'
                ], 422);
            }

            // Update ticket
            $userId = Auth::id();
            
            $ticket->update([
                'status' => 'refunded',
                'refunded_at' => now(),
                'refunded_by' => $userId,
            ]);

            // Update wallet (REFUND = ADD MONEY)
            $event = Event::where('id', Event::cachedActive()->id)->lockForUpdate()->first();
            $event->tellers()->updateExistingPivot($userId, [
                'current_wallet' => DB::raw('current_wallet - ' . (float)$ticket->amount)
            ]);

            // Broadcast after success
            broadcast(new BettingUpdated($ticket->round));

            app(TransactionService::class)->refund($ticket);

            return response()->json([
                'message' => 'Ticket refunded successfully',
                'ticket' => $ticket,
            ]);
        });
    }

    public function generateUniqueTicketNumber()
    {
        $attempts = 0;
        $maxAttempts = 15;

        while ($attempts < $maxAttempts) {
            // 1. Generate the 10-digit random number
            $candidate = str_pad(mt_rand(0, 9999999999), 10, '0', STR_PAD_LEFT);

            // 2. CHECK: Does it exist in the tickets table?
            $exists = DB::table('tickets')->where('ticket_number', $candidate)->exists();

            if (!$exists) {
                return $candidate; // It's unique, return it!
            }

            $attempts++;
        }

        throw new \Exception("Failed to generate a unique ticket number after $maxAttempts attempts.");
    }

    public function reprint(Ticket $ticket) 
    {
        // Ensure the teller can only access their own tickets for safety
        if ($ticket->teller_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $d = new DNS1D();

        // Return all fields required by the Ticket interface in your Vue component
        return response()->json([
            'ticket_number'    => $ticket->ticket_number,
            'round_id'         => $ticket->round_id,
            'side'             => $ticket->side,
            'amount'           => $ticket->amount,
            'potential_payout' => $ticket->potential_payout,
            'odds'             => $ticket->odds,
            'teller_name'      => $ticket->teller->name, // Assuming a relationship exists
            'date'             => $ticket->created_at->format('Y-m-d H:i:s'),
            'barcode_html' => $d->getBarcodeSVG($ticket->ticket_number, 'C128', 2, 45),
        ]);
    }
}
