<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventTellerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $startingBalance = 10000.00; // Set your desired initial cash

        // Create an array of User IDs from 4 to 23
        $tellerIds = range(4, 23);

        for ($eventId = 1; $eventId <= 2; $eventId++) {
            foreach ($tellerIds as $id) {
                DB::table('event_teller')->insert([
                    'event_id'       => $eventId,
                    'user_id'        => $id,
                    'initial_wallet' => $startingBalance,
                    'current_wallet' => $startingBalance,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        }
    }
}
