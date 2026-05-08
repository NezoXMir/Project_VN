<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subtasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_id')->constrained()->cascadeOnDelete();
            $table->string('title', 200);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index('goal_id');
            $table->index(['goal_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subtasks');
    }
};
