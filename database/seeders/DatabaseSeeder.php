<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'peneliti@lab.test'],
            [
                'name' => 'Peneliti Lab',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
            ],
        );
    }
}
