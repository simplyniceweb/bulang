<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('events')->insert([
            [
                'name' => '3 COCK DERBY',
                'status' => 'inactive',
            ],
            [
                'name' => '3 HITS',
                'status' => 'active'
            ]
        ]);
    }
}
