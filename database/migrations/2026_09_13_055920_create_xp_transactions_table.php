<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xp_transactions', function (Blueprint $table) {
            $table->uuid('transaction_id')->primary();
            $table->uuid('user_id');
            $table->enum('source_type', [
                'assessment_session',
                'pvp_match',
                'badge',
                'streak_bonus',
                'admin_adjustment'
            ]);
            $table->uuid('source_id')->nullable();
            $table->integer('xp_delta');
            $table->string('reason', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at'], 'idx_user_created');

            $table->foreign('user_id')
                  ->references('user_id')
                  ->on('users')
                  ->restrictOnDelete()
                  ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xp_transactions');
    }
};