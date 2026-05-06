<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('label', 60);
            $table->string('color', 7);
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->index('user_id');
        });

        // Системные категории (user_id = null, is_system = true).
        // Доступны всем пользователям, удалить нельзя. Палитра подобрана
        // под существующую цветовую схему UI.
        $now = now();
        DB::table('categories')->insert([
            ['user_id' => null, 'label' => 'Учёба',  'color' => '#4F46E5', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => null, 'label' => 'Спорт',  'color' => '#10B981', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => null, 'label' => 'Работа', 'color' => '#F59E0B', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
