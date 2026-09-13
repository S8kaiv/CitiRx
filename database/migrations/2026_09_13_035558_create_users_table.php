<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            // Primary key — UUID
            $table->uuid('user_id')->primary();

            // Personal info
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('middle_name', 100)->nullable();

            // Cohort & student number
            $table->uuid('cohort_id')->nullable();
            $table->string('student_id', 50)->nullable()->unique();

            // Authentication
            $table->string('email', 255)->unique();
            $table->string('password', 255);
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();

            // Role & flags
            $table->enum('role', ['student', 'faculty', 'admin'])->default('student');
            $table->boolean('is_diagnostic_completed')->default(false);

            // Progress & gamification
            $table->decimal('predicted_readiness_pct', 5, 2)->nullable();
            $table->unsignedInteger('total_xp')->default(0);
            $table->unsignedSmallInteger('current_level')->nullable();
            $table->unsignedInteger('streak_count')->default(0);
            $table->date('last_active_date')->nullable();

            // Soft delete
            $table->softDeletes();

            // Timestamps
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Foreign keys
            $table->foreign('cohort_id')
                  ->references('cohort_id')
                  ->on('cohorts')
                  ->restrictOnDelete()
                  ->cascadeOnUpdate();

            $table->foreign('current_level')
                  ->references('level_number')
                  ->on('levels')
                  ->restrictOnDelete()
                  ->cascadeOnUpdate();
        });

        // Check constraint (Laravel has no native method — use raw SQL)
        DB::statement(
            'ALTER TABLE users ADD CONSTRAINT chk_predicted_readiness '
            . 'CHECK (predicted_readiness_pct IS NULL OR predicted_readiness_pct BETWEEN 0 AND 100)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};