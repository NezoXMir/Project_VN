<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'              => 'Тестовый Администратор',
                'password'          => Hash::make('password'),
                'role'              => User::ROLE_ADMIN,
                'email_verified_at' => $now,
            ],
        );

        User::updateOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name'              => 'Тестовый Менеджер',
                'password'          => Hash::make('password'),
                'role'              => User::ROLE_MANAGER,
                'email_verified_at' => $now,
            ],
        );

        User::updateOrCreate(
            ['email' => 'user@example.com'],
            [
                'name'              => 'Тестовый пользователь',
                'password'          => Hash::make('password'),
                'role'              => User::ROLE_USER,
                'email_verified_at' => $now,
            ],
        );
    }
}
