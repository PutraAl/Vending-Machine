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
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'technician@example.com'],
            [
                'name' => 'Technician',
                'password' => Hash::make('password'),
                'role' => 'technician',
            ]
        );

        User::updateOrCreate(
            ['email' => 'operator@example.com'],
            [
                'name' => 'Operator',
                'password' => Hash::make('password'),
                'role' => 'operator',
            ]
        );
    }
}