<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $accounts = [
            ['name' => 'Owner Account', 'email' => 'owner@FreshTrack.ph', 'role' => 'owner'],
            ['name' => 'Manager Account', 'email' => 'manager@FreshTrack.ph', 'role' => 'manager'],
            ['name' => 'Cashier Account', 'email' => 'cashier@FreshTrack.ph', 'role' => 'cashier'],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => 'password',
                    'role' => $account['role'],
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
