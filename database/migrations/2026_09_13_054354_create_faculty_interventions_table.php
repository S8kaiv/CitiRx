<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faculty_interventions', function (Blueprint $table) {
            $table->uuid('intervention_id')->primary();
            $table->uuid('faculty_id');
            $table->uuid('target_user_id');
            $table->unsignedInteger('domain_id');
            $table->uuid('competency_id')->nullable();
            $table->enum('intervention_type', ['advisory', 'reminder', 'study_plan', 'meeting', 'other']);
            $table->text('custom_message')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();

            $table->index('target_user_id', 'idx_target_user');
            $table->index('competency_id', 'idx_competency');

            $table->foreign('faculty_id')
                  ->references('user_id')
                  ->on('users')
                  ->restrictOnDelete()
                  ->cascadeOnUpdate();

            $table->foreign('target_user_id')
                  ->references('user_id')
                  ->on('users')
                  ->restrictOnDelete()
                  ->cascadeOnUpdate();

            $table->foreign('domain_id')
                  ->references('domain_id')
                  ->on('tos_domains')
                  ->restrictOnDelete()
                  ->cascadeOnUpdate();

            $table->foreign('competency_id')
                  ->references('competency_id')
                  ->on('tos_competencies')
                  ->restrictOnDelete()
                  ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faculty_interventions');
    }
};