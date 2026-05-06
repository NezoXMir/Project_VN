<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Добавляем nullable FK — нельзя сразу NOT NULL, у существующих
        //    строк ещё нет category_id.
        Schema::table('goals', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('user_id')
                ->constrained('categories')->cascadeOnDelete();
        });

        // 2) Маппим старые строковые значения на id системных категорий.
        //    `other` мапится на «Учёба» (системная «Другое» удалена по
        //    решению пользователя — см. лог Этапа 04 (категории)).
        $map = [
            'study' => 'Учёба',
            'sport' => 'Спорт',
            'work'  => 'Работа',
            'other' => 'Учёба',
        ];

        $systemIds = DB::table('categories')
            ->where('is_system', true)
            ->pluck('id', 'label');

        foreach ($map as $oldCode => $label) {
            if (! isset($systemIds[$label])) {
                continue;
            }
            DB::table('goals')
                ->where('category', $oldCode)
                ->update(['category_id' => $systemIds[$label]]);
        }

        // 3) Удаляем старое enum-поле и делаем category_id обязательным.
        Schema::table('goals', function (Blueprint $table) {
            $table->dropColumn('category');
        });

        Schema::table('goals', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        // Возвращаем старое enum-поле, пытаемся восстановить значения
        // обратным маппингом (точность не гарантируется — `other`
        // не восстановится).
        Schema::table('goals', function (Blueprint $table) {
            $table->enum('category', ['study', 'sport', 'work', 'other'])
                ->nullable()->after('user_id');
        });

        $reverseMap = [
            'Учёба'  => 'study',
            'Спорт'  => 'sport',
            'Работа' => 'work',
        ];

        $systemIds = DB::table('categories')
            ->where('is_system', true)
            ->pluck('id', 'label');

        foreach ($reverseMap as $label => $oldCode) {
            if (! isset($systemIds[$label])) {
                continue;
            }
            DB::table('goals')
                ->where('category_id', $systemIds[$label])
                ->update(['category' => $oldCode]);
        }

        // Пользовательские категории мапим на 'other'.
        DB::table('goals')->whereNull('category')->update(['category' => 'other']);

        Schema::table('goals', function (Blueprint $table) {
            $table->enum('category', ['study', 'sport', 'work', 'other'])->nullable(false)->change();
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};
