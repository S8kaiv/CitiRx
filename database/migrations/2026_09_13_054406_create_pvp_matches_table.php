<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pvp_matches', function (Blueprint $table) {
            $table->uuid('match_id')->primary();
            $table->uuid('user_id');
            $table->uuid('session_id')->unique();  // enforces 1:1 with assessment_sessions
            $table->string('bot_persona_name', 100);
            $table->unsignedInteger('user_final_score')->default(0);
            $table->unsignedInteger('bot_final_score')->default(0);
            $table->enum('outcome', ['win', 'loss', 'draw']);
            $table->unsignedInteger('xp_earned')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')
                  ->references('user_id')
                  ->on('users')
                  ->restrictOnDelete()
                  ->cascadeOnUpdate();

            $table->foreign('session_id')
                  ->references('session_id')
                  ->on('assessment_sessions')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pvp_matches');
    }
};