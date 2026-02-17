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
        'result',
        'total_red',
        'total_green',
        'house_cut',
        'opened_at',
        'closed_at',
        'settled_at',
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
