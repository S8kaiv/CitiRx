<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE xp_transactions
             MODIFY COLUMN source_type
             ENUM(
                'assessment_session',
                'response_telemetry_log',
                'pvp_match',
                'badge',
                'streak_bonus',
                'admin_adjustment'
             )
             NOT NULL"
        );
    }

    public function down(): void
    {
        /*
         * MySQL cannot shrink the ENUM while rows still
         * contain response_telemetry_log.
         *
         * During a development rollback we convert those
         * rows to admin_adjustment before restoring the
         * previous ENUM.
         */
        DB::table('xp_transactions')
            ->where(
                'source_type',
                'response_telemetry_log'
            )
            ->update([
                'source_type' => 'admin_adjustment',
            ]);

        DB::statement(
            "ALTER TABLE xp_transactions
             MODIFY COLUMN source_type
             ENUM(
                'assessment_session',
                'pvp_match',
                'badge',
                'streak_bonus',
                'admin_adjustment'
             )
             NOT NULL"
        );
    }
};
