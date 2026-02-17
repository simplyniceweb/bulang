<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        // Game Master
        User::create([
            'name' => 'Game Master',
            'username' => 'master',
            'password' => Hash::make('master123'),
            'role' => 'game_master',
            'status' => 'active',
        ]);

        for ($i = 1; $i <= 20; $i++) {
            User::create([
                'name' => "Teller $i",
                'username' => "teller$i",
                'password' => Hash::make("teller$i"),
                'role' => 'teller',
                'status' => 'active',
            ]);
        }
    }
}
