<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('response_telemetry_logs', function (Blueprint $table) {
            $table->uuid('log_id')->primary();
            $table->uuid('session_id');
            $table->uuid('user_id');
            $table->uuid('question_id');
            $table->uuid('competency_id');
            $table->uuid('selected_choice_id');
            $table->boolean('is_correct');
            $table->decimal('response_time_seconds', 6, 2);
            $table->unsignedSmallInteger('item_position')->nullable();
            $table->boolean('is_speed_flagged')->default(false);
            $table->decimal('prior_p_l', 5, 4);
            $table->decimal('posterior_p_l', 5, 4);
            $table->timestamp('created_at')->useCurrent();

            // Indexes for common queries
            $table->index(['user_id', 'created_at'], 'idx_user_created');
            $table->index('competency_id', 'idx_competency');

            // Five foreign keys — all RESTRICT/CASCADE
            $table->foreign('session_id')
                  ->references('session_id')
                  ->on('assessment_sessions')
                  ->restrictOnDelete()
                  ->cascadeOnUpdate();

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

            $table->foreign('competency_id')
                  ->references('competency_id')
                  ->on('tos_competencies')
                  ->restrictOnDelete()
                  ->cascadeOnUpdate();

            $table->foreign('selected_choice_id')
                  ->references('choice_id')
                  ->on('question_choices')
                  ->restrictOnDelete()
                  ->cascadeOnUpdate();
        });

        // CHECK constraints — raw SQL because Blueprint has no ->check()
        DB::statement(
            'ALTER TABLE response_telemetry_logs ADD CONSTRAINT chk_response_time '
            . 'CHECK (response_time_seconds >= 0)'
        );

        DB::statement(
            'ALTER TABLE response_telemetry_logs ADD CONSTRAINT chk_prior_range '
            . 'CHECK (prior_p_l BETWEEN 0 AND 1)'
        );

        DB::statement(
            'ALTER TABLE response_telemetry_logs ADD CONSTRAINT chk_posterior_range '
            . 'CHECK (posterior_p_l BETWEEN 0 AND 1)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('response_telemetry_logs');
    }
};