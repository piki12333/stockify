<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Administrator',
                'email' => 'admin@stockify.test',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Budi Santoso (Manajer Gudang)',
                'email' => 'manager@stockify.test',
                'password' => Hash::make('password'),
                'role' => User::ROLE_MANAGER,
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Doni Pratama (Staff Gudang)',
                'email' => 'staff@stockify.test',
                'password' => Hash::make('password'),
                'role' => User::ROLE_STAFF,
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}

