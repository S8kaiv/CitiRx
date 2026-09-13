<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iso_survey_evaluations', function (Blueprint $table) {
            $table->uuid('evaluation_id')->primary();
            $table->uuid('user_id');
            $table->enum('evaluator_role', ['student', 'faculty', 'it_professional']);
            $table->boolean('has_submitted_external_form')->default(false);
            $table->decimal('overall_mean_score', 3, 2)->nullable();
            $table->timestamp('submitted_at')->nullable();

            $table->unique(['user_id', 'evaluator_role'], 'uq_user_evaluator_role');

            $table->foreign('user_id')
                  ->references('user_id')
                  ->on('users')
                  ->restrictOnDelete()
                  ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iso_survey_evaluations');
    }
};