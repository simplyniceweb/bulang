<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Round extends Model
{
    use HasFactory;

    protected $appends = ['payout_details'];

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
        'betting_closed',
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
        'betting_closed' => 'boolean',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function getPayoutDetailsAttribute()
    {
        $housePercent = $this->event?->house_percent ?? 6.00;
        $plasada =  $housePercent / 100; // % commission

        // Use the sums from 'withSum' if they exist, otherwise perform the query
        // (The ?? fallback is useful for Echo broadcasts where sums might not be pre-loaded)
        $totalMeron = $this->meron_sum ?? $this->tickets()->where('side', 'meron')->sum('amount') ?? 0;
        $totalWala = $this->wala_sum ?? $this->tickets()->where('side', 'wala')->sum('amount') ?? 0;
        $totalDraw = $this->draw_sum ?? $this->tickets()->where('side', 'draw')->sum('amount') ?? 0;
        
        $totalPool = $totalMeron + $totalWala;
        $netPool = $totalPool * (1 - $plasada);

        // Odds Calculation (The "Payout" number on your screen, e.g., 227.37)
        // Formula: (Net Pool / Side Total) * 100
        $meronPayout = $totalMeron > 0 ? ($netPool / $totalMeron) * 100 : 0;
        $walaPayout = $totalWala > 0 ? ($netPool / $totalWala) * 100 : 0;

        return [
            'meron_total' => (float)$totalMeron,
            'wala_total'  => (float)$totalWala,
            'draw_total' => (float)$totalDraw,
            'meron_payout' => round($meronPayout, 2),
            'wala_payout'  => round($walaPayout, 2),
            'draw_multiplier' => 7, // Fixed as per your request
        ];
    }

    // public function getPayoutDetailsAttribute()
    // {
    //     // $housePercent = $this->event?->house_percent ?? 6.00;
    //     $housePercent = ($this->event?->house_percent ?? 6.00) * 2;
    //     $commissionMultiplier = $housePercent / 100;

    //     $totalMeron = (float)($this->meron_sum ?? $this->tickets()->where('side', 'meron')->sum('amount') ?? 0);
    //     $totalWala = (float)($this->wala_sum ?? $this->tickets()->where('side', 'wala')->sum('amount') ?? 0);
    //     $totalDraw = (float)($this->draw_sum ?? $this->tickets()->where('side', 'draw')->sum('amount') ?? 0);
        
    //     // Calculate individual net amounts (6% taken from each side)
    //     $netMeron = $totalMeron * (1 - $commissionMultiplier);
    //     $netWala = $totalWala * (1 - $commissionMultiplier);

    //     /**
    //      * Logic: If Meron wins, the payout comes from (Total Meron + Total Wala) - Total 6% Commission.
    //      * This is mathematically the same as (Net Meron + Net Wala).
    //      */
    //     $totalNetPool = $netMeron + $netWala;

    //     // Payout per 100 bet
    //     $meronPayout = $totalMeron > 0 ? ($totalNetPool / $totalMeron) * 100 : 0;
    //     $walaPayout = $totalWala > 0 ? ($totalNetPool / $totalWala) * 100 : 0;

    //     return [
    //         'meron_total'     => $totalMeron,
    //         'wala_total'      => $totalWala,
    //         'draw_total'      => $totalDraw,
    //         'meron_payout'    => round($meronPayout, 2),
    //         'wala_payout'     => round($walaPayout, 2),
    //         'meron_odds'      => $totalMeron > 0 ? round($totalNetPool / $totalMeron, 2) : 0,
    //         'wala_odds'       => $totalWala > 0 ? round($totalNetPool / $totalWala, 2) : 0,
    //         'draw_multiplier' => 7,
    //         'house_take'      => round(($totalMeron + $totalWala) * $commissionMultiplier, 2),
    //     ];
    // }
}
