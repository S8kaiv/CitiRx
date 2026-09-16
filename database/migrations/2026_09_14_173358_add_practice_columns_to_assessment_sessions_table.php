<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_sessions', function (Blueprint $table) {

            /*
             * Practice target:
             * 10, 20, or 30 questions.
             *
             * Nullable because diagnostic/mock/PvP sessions
             * do not necessarily use this field.
             */
            $table->unsignedSmallInteger('target_length')
                ->nullable()
                ->after('served_question_ids');

            /*
             * Optional practice domain filter.
             *
             * NULL = adaptive practice across all domains.
             *
             * tos_domains.domain_id uses INT UNSIGNED,
             * so this must also be unsignedInteger().
             */
            $table->unsignedInteger('domain_filter_id')
                ->nullable()
                ->after('target_length');

            /*
             * The practice question currently being shown.
             *
             * Practice serves only one question at a time.
             */
            $table->uuid('current_question_id')
                ->nullable()
                ->after('domain_filter_id');

            /*
             * Server-side timestamp used to calculate
             * response time.
             */
            $table->timestamp('current_question_started_at')
                ->nullable()
                ->after('current_question_id');

            /*
             * Optional selected domain.
             *
             * If that domain is ever deleted,
             * existing practice sessions remain valid
             * and simply lose the filter.
             */
            $table->foreign('domain_filter_id')
                ->references('domain_id')
                ->on('tos_domains')
                ->nullOnDelete();

            /*
             * Current practice question.
             *
             * If the question is ever removed,
             * the session can recover by selecting another.
             */
            $table->foreign('current_question_id')
                ->references('question_id')
                ->on('questions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('assessment_sessions', function (Blueprint $table) {

            $table->dropForeign([
                'domain_filter_id',
            ]);

            $table->dropForeign([
                'current_question_id',
            ]);

            $table->dropColumn([
                'target_length',
                'domain_filter_id',
                'current_question_id',
                'current_question_started_at',
            ]);
        });
    }
};
