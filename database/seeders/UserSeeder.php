<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
            'qr_code_key' => (string) 'admin_' . Str::ulid()
        ]);

        // Game Master
        User::create([
            'name' => 'Game Master',
            'username' => 'master',
            'password' => Hash::make('master123'),
            'role' => 'game_master',
            'status' => 'active',
            'qr_code_key' => (string) 'master_' . Str::ulid()
        ]);

        // Supervisor
        User::create([
            'name' => 'Supervisor',
            'username' => 'supervisor',
            'password' => Hash::make('supervisor123'),
            'role' => 'supervisor',
            'status' => 'active',
            'qr_code_key' => (string) 'supervisor_' . Str::ulid()
        ]);

        for ($i = 1; $i <= 20; $i++) {
            User::create([
                'name' => "Teller $i",
                'username' => "teller$i",
                'password' => Hash::make("teller$i"),
                'role' => 'teller',
                'status' => 'active',
                'qr_code_key' => (string) 'teller_' . Str::ulid()
            ]);
        }
    }
}
