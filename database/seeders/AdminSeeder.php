<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            [
                'username' => 'admin',
            ],
            [
                'name' => 'Administrator',
                'email' => null,
                'password' => 'Admin123!',
                'role' => 'admin',
                'status' => 'active',
                'department' => null,
                'skills' => null,
                'phone' => null,
                'joined_at' => now()->toDateString(),
                'must_change_password' => true,
            ]
        );
    }
}
