<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 2 пользователя с ролью 1 (например админы)
        User::create([
            'name' => 'Admin One',
            'email' => 'admin1@test.com',
            'role' => 0,
            'password' => Hash::make('password'),
        ]);

        User::create([
            'name' => 'Admin Two',
            'email' => 'admin2@test.com',
            'role' => 0,
            'password' => Hash::make('password'),
        ]);

        // 4 пользователя с ролью 2 (например мастера)
        User::create([
            'name' => 'Master One',
            'email' => 'master1@test.com',
            'role' => 1,
            'password' => Hash::make('password'),
        ]);

        User::create([
            'name' => 'Master Two',
            'email' => 'master2@test.com',
            'role' => 1,
            'password' => Hash::make('password'),
        ]);

        User::create([
            'name' => 'Master Three',
            'email' => 'master3@test.com',
            'role' => 1,
            'password' => Hash::make('password'),
        ]);

        User::create([
            'name' => 'Master Four',
            'email' => 'master4@test.com',
            'role' => 1,
            'password' => Hash::make('password'),
        ]);
    }
}
