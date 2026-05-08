<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 80);
            $table->string('description', 200);
            $table->string('icon', 16);
            $table->unsignedInteger('threshold')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // 8 системных достижений вставляются прямо в миграцию —
        // часть схемы, не «тестовые данные».
        $now = now();
        DB::table('achievements')->insert([
            ['code' => 'first_task',   'name' => 'Первый шаг',     'description' => 'Завершена первая задача',         'icon' => '👶', 'threshold' => 1,   'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'tasks_10',     'name' => 'Десятка',        'description' => 'Завершено 10 задач',              'icon' => '🔟', 'threshold' => 10,  'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'tasks_100',    'name' => 'Сотня',          'description' => 'Завершено 100 задач',             'icon' => '💯', 'threshold' => 100, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'first_goal',   'name' => 'Финишёр',        'description' => 'Завершена первая цель',           'icon' => '🏁', 'threshold' => 1,   'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'goals_5',      'name' => 'Пятёрка целей',  'description' => 'Завершено 5 целей',               'icon' => '🏆', 'threshold' => 5,   'sort_order' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'streak_7',     'name' => 'Неделя огня',    'description' => 'Серия 7 дней подряд с задачами',  'icon' => '🔥', 'threshold' => 7,   'sort_order' => 6, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'streak_30',    'name' => 'Месяц фокуса',   'description' => 'Серия 30 дней подряд с задачами', 'icon' => '🎯', 'threshold' => 30,  'sort_order' => 7, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'own_category', 'name' => 'Свой стиль',     'description' => 'Создана собственная категория',   'icon' => '🎨', 'threshold' => 1,   'sort_order' => 8, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
