<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'assessment_sessions',
            function (Blueprint $table) {
                $table->timestamp('expires_at')
                    ->nullable()
                    ->after('current_question_started_at');

                $table->decimal(
                    'readiness_snapshot_pct',
                    5,
                    2,
                )
                    ->nullable()
                    ->after('expires_at');

                $table->json('domain_mastery_snapshot')
                    ->nullable()
                    ->after('readiness_snapshot_pct');

                $table->string(
                    'exam_form_version',
                    40,
                )
                    ->nullable()
                    ->after('domain_mastery_snapshot');

                $table->enum(
                    'submission_reason',
                    [
                        'manual',
                        'expired',
                    ],
                )
                    ->nullable()
                    ->after('exam_form_version');

                $table->index(
                    [
                        'session_type',
                        'research_phase',
                        'completed_at',
                        'expires_at',
                    ],
                    'idx_expired_research_sessions',
                );
            }
        );

        Schema::table(
            'cohorts',
            function (Blueprint $table) {
                $table->timestamp('post_test_opens_at')
                    ->nullable()
                    ->after('target_phle_date');

                $table->timestamp('post_test_closes_at')
                    ->nullable()
                    ->after('post_test_opens_at');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'assessment_sessions',
            function (Blueprint $table) {
                $table->dropIndex(
                    'idx_expired_research_sessions'
                );

                $table->dropColumn([
                    'expires_at',
                    'readiness_snapshot_pct',
                    'domain_mastery_snapshot',
                    'exam_form_version',
                    'submission_reason',
                ]);
            }
        );

        Schema::table(
            'cohorts',
            function (Blueprint $table) {
                $table->dropColumn([
                    'post_test_opens_at',
                    'post_test_closes_at',
                ]);
            }
        );
    }
};
