<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class RoundTicketSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            for ($eventId = 1; $eventId <= 2; $eventId++) {

                $now = Carbon::now();

                for ($roundNumber = 1; $roundNumber <= 120; $roundNumber++) {

                    $winner = rand(0, 1) ? 'meron' : 'wala';

                    // ================================
                    // CREATE ROUND
                    // ================================
                    $roundId = DB::table('rounds')->insertGetId([
                        'event_id' => $eventId,
                        'round_number' => $roundNumber,
                        'status' => 'closed',
                        'winner' => $winner,

                        'total_meron' => 0,
                        'total_wala' => 0,
                        'total_draw' => 0,

                        'house_cut' => 0,
                        'house_percent' => 6.00,

                        'opened_at' => $now->copy()->subMinutes(5),
                        'closed_at' => $now,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $round = DB::table('rounds')->find($roundId);

                    $totalMeron = 0;
                    $totalWala = 0;
                    $totalDraw = 0;

                    // ================================
                    // TICKETS GENERATION
                    // ================================
                    for ($tellerId = 4; $tellerId <= 23; $tellerId++) {

                        $ticketCount = rand(15, 25);

                        for ($i = 1; $i < $ticketCount; $i++) {

                            $side = rand(0, 1) ? 'meron' : 'wala';
                            $amount = rand(100, 5000);
                            $isWin = $side === $winner;

                            if ($side === 'meron') {
                                $totalMeron += $amount;
                            } else {
                                $totalWala += $amount;
                            }

                            // ================================
                            // CREATE TICKET
                            // ================================
                            $odds = rand(150, 250) / 100;
                            $ticketNumber = $this->generateUniqueTicketNumber();

                            $ticketId = DB::table('tickets')->insertGetId([
                                'ticket_number' => $ticketNumber,
                                'status' => $isWin ? 'won' : 'lost',

                                'event_id' => $eventId,
                                'round_id' => $round->round_number,
                                'teller_id' => $tellerId,

                                'side' => $side,
                                'amount' => $amount,
                                'odds' => $odds,
                                'potential_payout' => $amount * $odds,

                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            // ================================
                            // BET TRANSACTION (IN)
                            // ================================
                            $wallet = DB::table('event_teller')
                                ->where('event_id', $eventId)
                                ->where('user_id', $tellerId)
                                ->first();

                            $beforeBet = $wallet->current_wallet;
                            $afterBet = $beforeBet + $amount;

                            DB::table('event_teller')
                                ->where('event_id', $eventId)
                                ->where('user_id', $tellerId)
                                ->update([
                                    'current_wallet' => $afterBet
                                ]);

                            DB::table('transactions')->insert([
                                'teller_id' => $tellerId,
                                'event_id' => $eventId,
                                'round_id' => $roundId,
                                'ticket_id' => $ticketId,

                                'type' => 'bet',
                                'direction' => 'in',
                                'amount' => $amount,

                                'balance_before' => $beforeBet,
                                'balance_after' => $afterBet,

                                'created_at' => now(),
                            ]);

                            // ================================
                            // CLAIM TRANSACTION (OUT - WIN ONLY)
                            // ================================
                            if ($isWin) {

                                $payout = $amount * 1.9; // mock odds

                                $wallet = DB::table('event_teller')
                                    ->where('event_id', $eventId)
                                    ->where('user_id', $tellerId)
                                    ->first();

                                $beforeClaim = $wallet->current_wallet;
                                $afterClaim = $beforeClaim - $payout;

                                DB::table('event_teller')
                                    ->where('event_id', $eventId)
                                    ->where('user_id', $tellerId)
                                    ->update([
                                        'current_wallet' => $afterClaim
                                    ]);

                                DB::table('transactions')->insert([
                                    'teller_id' => $tellerId,
                                    'event_id' => $eventId,
                                    'round_id' => $roundId,
                                    'ticket_id' => $ticketId,

                                    'type' => 'claim',
                                    'direction' => 'out',
                                    'amount' => $payout,

                                    'balance_before' => $beforeClaim,
                                    'balance_after' => $afterClaim,

                                    'created_at' => now(),
                                ]);
                            }
                        }
                    }

                    // ================================
                    // UPDATE ROUND TOTALS
                    // ================================
                    $totalPool = $totalMeron + $totalWala;
                    $houseCut = $totalPool * 0.06;

                    DB::table('rounds')->where('id', $roundId)->update([
                        'total_meron' => $totalMeron,
                        'total_wala' => $totalWala,
                        'total_draw' => $totalDraw,
                        'house_cut' => $houseCut,
                    ]);
                }
            }
        });

        for ($roundNumber = 1; $roundNumber <= 120; $roundNumber++) {
            
            $payoutDetails = DB::table('rounds')->where('id', $roundNumber)->first();

            $totalMeron = $payoutDetails->total_meron;
            $totalWala = $payoutDetails->total_wala;

            $netPool = ($totalMeron + $totalWala) * 0.94;

            $meronOdds = $totalMeron > 0 ? $netPool / $totalMeron : 0;
            $walaOdds  = $totalWala > 0 ? $netPool / $totalWala : 0;

            DB::table('tickets')
            ->where('round_id', $roundNumber)
            ->update([
                'odds' => DB::raw("
                    CASE 
                        WHEN side = 'meron' THEN {$meronOdds}
                        WHEN side = 'wala' THEN {$walaOdds}
                        ELSE 7
                    END
                "),
                'potential_payout' => DB::raw("
                    amount * CASE 
                        WHEN side = 'meron' THEN {$meronOdds}
                        WHEN side = 'wala' THEN {$walaOdds}
                        ELSE 7
                    END
                ")
            ]);
        }
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
}