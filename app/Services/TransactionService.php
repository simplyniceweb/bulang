<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function bet(Ticket $ticket)
    {
        // avoid duplication
        if (Transaction::where('ticket_id', $ticket->id)
            ->where('type', 'bet')
            ->exists()) {
                return;
        }
        
        return Transaction::create([
            'teller_id' => $ticket->teller_id,
            'event_id'  => $ticket->event_id,
            'round_id'  => $ticket->round_id,
            'ticket_id' => $ticket->id,
            'type'      => 'bet',
            'direction' => 'in',
            'amount'    => $ticket->amount,
        ]);
    }

    public function payout(Ticket $ticket)
    {
        return DB::transaction(function () use ($ticket) {

            return Transaction::create([
                'teller_id' => $ticket->teller_id,
                'event_id'  => $ticket->event_id,
                'round_id'  => $ticket->round_id,
                'ticket_id' => $ticket->id,
                'type'      => 'claim',
                'direction' => 'out',
                'amount'    => $ticket->potential_payout,
            ]);
        });
    }

    public function refund(Ticket $ticket)
    {
        return DB::transaction(function () use ($ticket) {

            return Transaction::create([
                'teller_id' => $ticket->teller_id,
                'event_id'  => $ticket->event_id,
                'round_id'  => $ticket->round_id,
                'ticket_id' => $ticket->id,
                'type'      => 'refund',
                'direction' => 'out',
                'amount'    => $ticket->amount,
            ]);
        });
    }
}