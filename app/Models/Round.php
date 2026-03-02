<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Round extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'round_number',
        'status',
        'winner',
        'total_meron',
        'total_wala',
        'total_draw',
        'house_cut',
        'meron_closed',
        'wala_closed',
        'draw_closed',
        'opened_at',
        'closed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'meron_closed' => 'boolean',
        'wala_closed' => 'boolean',
        'draw_closed' => 'boolean',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
