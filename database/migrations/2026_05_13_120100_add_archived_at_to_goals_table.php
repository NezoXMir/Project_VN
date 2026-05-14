<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goals', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('deadline');
            $table->index('archived_at');
        });

        // Существующие архивные цели получают archived_at = updated_at,
        // чтобы сортировка «новое сверху» в архиве работала сразу.
        DB::statement("UPDATE `goals` SET `archived_at` = `updated_at` WHERE `status` = 'archived' AND `archived_at` IS NULL");
    }

    public function down(): void
    {
        Schema::table('goals', function (Blueprint $table) {
            $table->dropIndex(['archived_at']);
            $table->dropColumn('archived_at');
        });
    }
};
