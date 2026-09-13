<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_sessions', function (Blueprint $table) {
            $table->uuid('session_id')->primary();
            $table->uuid('user_id');
            $table->enum('session_type', ['diagnostic', 'practice', 'mock_board', 'pvp']);
            $table->enum('research_phase', ['none', 'pre_test', 'post_test'])->default('none');
            $table->unsignedInteger('total_items')->default(0);
            $table->unsignedInteger('correct_items')->default(0);
            $table->unsignedInteger('xp_awarded')->default(0);
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['user_id', 'started_at'], 'idx_user_started');
            $table->index(['user_id', 'research_phase'], 'idx_research_phase');

            $table->foreign('user_id')
                  ->references('user_id')
                  ->on('users')
                  ->restrictOnDelete()
                  ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_sessions');
    }
};