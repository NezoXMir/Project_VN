<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subtask_id')->constrained()->cascadeOnDelete();
            $table->string('title', 200);
            $table->boolean('is_done')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index('subtask_id');
            $table->index(['subtask_id', 'position']);
            $table->index(['subtask_id', 'is_done']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
