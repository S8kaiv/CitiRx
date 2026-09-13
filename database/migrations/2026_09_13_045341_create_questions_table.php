<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->uuid('question_id')->primary();
            $table->uuid('competency_id');
            $table->text('question_text');
            $table->text('hypercorrection_rationale')->nullable();
            $table->boolean('is_diagnostic_pool')->default(false);
            $table->decimal('difficulty_index_p', 4, 3)->nullable();
            $table->unsignedInteger('speed_flag_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('competency_id')
                  ->references('competency_id')
                  ->on('tos_competencies')
                  ->restrictOnDelete()
                  ->cascadeOnUpdate();
        });

        DB::statement(
            'ALTER TABLE questions ADD CONSTRAINT chk_difficulty_index '
            . 'CHECK (difficulty_index_p IS NULL OR difficulty_index_p BETWEEN 0 AND 1)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};