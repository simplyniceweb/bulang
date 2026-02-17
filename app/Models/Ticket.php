<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'event_id',
        'round_id',
        'teller_id',
        'paid_by',
        'side',
        'amount',
        'odds',
        'potential_payout',
        'claimed_at',
    ];

    protected $dates = [
        'claimed_at',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function teller()
    {
        return $this->belongsTo(User::class, 'teller_id');
    }

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function transactions()
    {
        return $this->hasMany(TellerTransaction::class);
    }
}
