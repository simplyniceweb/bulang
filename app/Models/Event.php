<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'house_percent',
        'status',
        'started_at',
        'ended_at',
        'halt_event',
        'halt_count',
        'supervisor_wallet'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    protected $dates = ['deleted_at'];

    public function rounds()
    {
        return $this->hasMany(Round::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public static function activeOrFail()
    {
        $events = self::where('status','active')->get();

        if ($events->count() === 0) {
            throw ValidationException::withMessages([
                'event' => 'No active event found.'
            ]);
        }

        if ($events->count() > 1) {
            throw ValidationException::withMessages([
                'event' => 'Multiple active events detected.'
            ]);
        }

        return $events->first();
    }

    public static function cachedActive()
    {
        return Cache::remember('active_event', 60, function () {
            return self::activeOrFail();
        });
    }

    protected static function booted()
    {
        static::saved(function ($event) {
            Cache::forget('active_event');
        });
    }

    public function tellers()
    {
        return $this->belongsToMany(User::class, 'event_teller')
                    ->withPivot('initial_wallet', 'current_wallet')
                    ->withTimestamps();
    }
}
