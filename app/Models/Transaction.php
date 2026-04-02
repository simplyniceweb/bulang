<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'teller_id',
        'event_id',
        'round_id',
        'ticket_id',
        'type',
        'amount',
        'created_at',
        'authorized_by',
        'balance_before',
        'balance_after'
    ];

    public function teller()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function round()
    {
        return $this->belongsTo(Round::class);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function authorizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }
}
