<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'owner@alas.com'],
            [
                'name' => 'ALAS Owner',
                'username' => 'owner',
                'password' => Hash::make('password'),
                'role' => 'owner',
                'status' => 'active',
            ]
        );

        User::updateOrCreate(
            ['email' => 'manager@alas.com'],
            [
                'name' => 'Operations Manager',
                'username' => 'manager',
                'password' => Hash::make('password'),
                'role' => 'manager',
                'status' => 'active',
            ]
        );

        User::updateOrCreate(
            ['email' => 'staff@alas.com'],
            [
                'name' => 'Store Staff',
                'username' => 'staff',
                'password' => Hash::make('password'),
                'role' => 'staff',
                'status' => 'active',
            ]
        );
    }
}
