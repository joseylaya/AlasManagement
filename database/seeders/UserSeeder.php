<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'owner@alas.com'],
            [
                'name' => 'ALAS Owner',
                'password' => Hash::make('password'),
                'role' => 'owner',
                'status' => 'active',
            ]
        );

        User::firstOrCreate(
            ['email' => 'manager@alas.com'],
            [
                'name' => 'Operations Manager',
                'password' => Hash::make('password'),
                'role' => 'manager',
                'status' => 'active',
            ]
        );

        User::firstOrCreate(
            ['email' => 'staff@alas.com'],
            [
                'name' => 'Store Staff',
                'password' => Hash::make('password'),
                'role' => 'staff',
                'status' => 'active',
            ]
        );
    }
}
