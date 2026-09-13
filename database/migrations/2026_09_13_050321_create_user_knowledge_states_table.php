<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_knowledge_states', function (Blueprint $table) {
            $table->uuid('state_id')->primary();
            $table->uuid('user_id');
            $table->uuid('competency_id');
            $table->decimal('current_mastery_p_l', 5, 4)->default(0);
            $table->unsignedInteger('total_attempts')->default(0);
            $table->unsignedInteger('total_correct')->default(0);
            $table->timestamp('last_evaluated_at')->nullable();

            $table->unique(['user_id', 'competency_id'], 'uq_user_competency');

            $table->foreign('user_id')
                  ->references('user_id')
                  ->on('users')
                  ->restrictOnDelete()
                  ->cascadeOnUpdate();

            $table->foreign('competency_id')
                  ->references('competency_id')
                  ->on('tos_competencies')
                  ->restrictOnDelete()
                  ->cascadeOnUpdate();
        });

        DB::statement(
            'ALTER TABLE user_knowledge_states ADD CONSTRAINT chk_mastery_range '
            . 'CHECK (current_mastery_p_l BETWEEN 0 AND 1)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('user_knowledge_states');
    }
};