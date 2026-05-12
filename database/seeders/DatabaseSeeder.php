<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Главный сидер. Запускается через `php artisan migrate --seed`
     * или `db:seed` без аргументов. Делегирует в специализированные сидеры.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            AdminSeeder::class,
        ]);
    }
}
