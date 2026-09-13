<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rx_vault', function (Blueprint $table) {
            $table->uuid('vault_id')->primary();
            $table->uuid('user_id');
            $table->uuid('question_id');
            $table->unsignedInteger('consecutive_correct_count')->default(0);
            $table->boolean('is_cleared')->default(false);
            $table->timestamp('added_at')->useCurrent();
            $table->timestamp('cleared_at')->nullable();

            $table->unique(['user_id', 'question_id'], 'uq_user_question_vault');
            $table->index(['user_id', 'is_cleared'], 'idx_user_cleared');

            $table->foreign('user_id')
                  ->references('user_id')
                  ->on('users')
                  ->restrictOnDelete()
                  ->cascadeOnUpdate();

            $table->foreign('question_id')
                  ->references('question_id')
                  ->on('questions')
                  ->restrictOnDelete()
                  ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rx_vault');
    }
};