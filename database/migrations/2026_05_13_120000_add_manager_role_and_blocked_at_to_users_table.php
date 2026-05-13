<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('user','manager','admin') NOT NULL DEFAULT 'user'");

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('blocked_at')->nullable()->after('email_reminders_enabled');
            $table->index('blocked_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['blocked_at']);
            $table->dropColumn('blocked_at');
        });

        DB::statement("UPDATE `users` SET `role` = 'user' WHERE `role` = 'manager'");
        DB::statement("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('user','admin') NOT NULL DEFAULT 'user'");
    }
};
