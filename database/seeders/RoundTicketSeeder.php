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

            $eventId = 1; // change if needed
            $now = Carbon::now();

            for ($roundNumber = 1; $roundNumber <= 120; $roundNumber++) {

                $winner = rand(0, 1) ? 'meron' : 'wala';

                // Create round
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

                $totalMeron = 0;
                $totalWala = 0;

                $ticketsData = [];

                for ($tellerId = 3; $tellerId <= 22; $tellerId++) {

                    $ticketCount = rand(15, 25);

                    for ($i = 0; $i < $ticketCount; $i++) {

                        $side = rand(0, 1) ? 'meron' : 'wala';
                        $amount = rand(100, 5000);

                        if ($side === 'meron') {
                            $totalMeron += $amount;
                        } else {
                            $totalWala += $amount;
                        }

                        $ticketsData[] = [
                            'ticket_number' => "{$eventId}-{$roundId}-{$i}-" . strtoupper(Str::random(6)),
                            'status' => $side === $winner ? 'won' : 'lost',

                            'event_id' => $eventId,
                            'round_id' => $roundId,

                            'teller_id' => $tellerId,

                            'side' => $side,
                            'amount' => $amount,
                            'odds' => 0,
                            'potential_payout' => 0,

                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
                
                DB::table('tickets')->insert($ticketsData);

                // Compute house cut (6%)
                $totalPool = $totalMeron + $totalWala;
                $houseCut = $totalPool * 0.06;

                // Update round totals
                DB::table('rounds')->where('id', $roundId)->update([
                    'total_meron' => $totalMeron,
                    'total_wala' => $totalWala,
                    'house_cut' => $houseCut,
                ]);
            }
        });
    }
}