<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_sessions', function (Blueprint $table) {
            $table->string('practice_mode', 20)
                ->default('adaptive')
                ->after('target_length');

            /*
             * Supports:
             * - Dashboard unfinished-session lookup
             * - Practice-session resume lookup
             * - Targeted drill resume lookup
             */
            $table->index(
                [
                    'user_id',
                    'session_type',
                    'completed_at',
                    'practice_mode',
                    'started_at',
                ],
                'idx_session_resume_mode'
            );
        });
    }

    public function down(): void
    {
        Schema::table('assessment_sessions', function (Blueprint $table) {
            $table->dropIndex('idx_session_resume_mode');
            $table->dropColumn('practice_mode');
        });
    }
};