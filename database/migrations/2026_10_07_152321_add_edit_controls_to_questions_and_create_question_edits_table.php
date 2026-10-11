<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            /*
             * Indexed denormalized value used to determine whether
             * question content must be permanently frozen.
             */
            $table->timestamp('first_served_at')
                ->nullable()
                ->after('research_validated_by');

            /*
             * Optimistic concurrency control.
             *
             * Prevents one administrator from accidentally
             * overwriting another administrator's newer edit.
             */
            $table->unsignedInteger('edit_version')
                ->default(1)
                ->after('first_served_at');

            $table->index(
                'first_served_at',
                'idx_questions_first_served',
            );
        });

        Schema::create('question_edits', function (Blueprint $table) {
            $table->uuid('edit_id')->primary();

            $table->uuid('question_id');
            $table->uuid('editor_id');

            /*
             * Examples:
             *
             * content_updated
             * deactivated
             * reactivated
             */
            $table->string('action', 40);

            /*
             * One JSON document per operation:
             *
             * {
             *   "question_text": {
             *     "old": "...",
             *     "new": "..."
             *   }
             * }
             */
            $table->json('changes');

            $table->text('reason')->nullable();

            $table->unsignedInteger('version_before');
            $table->unsignedInteger('version_after');

            $table->timestamp('edited_at')
                ->useCurrent();

            /*
             * Do not cascade-delete question audit records.
             *
             * The audit trail must prevent the question from being
             * hard-deleted after audited changes have been recorded.
             */
            $table->foreign('question_id')
                ->references('question_id')
                ->on('questions')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            /*
             * Users are soft-deleted in CitiRx, so restrictOnDelete
             * does not interfere with normal account deletion.
             */
            $table->foreign('editor_id')
                ->references('user_id')
                ->on('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->index(
                [
                    'question_id',
                    'edited_at',
                ],
                'idx_question_edits_history',
            );

            $table->index(
                [
                    'editor_id',
                    'edited_at',
                ],
                'idx_question_edits_editor',
            );
        });

        /*
         * Preserve history that existed before first_served_at
         * was introduced.
         */
        $this->backfillFirstServedAt();
    }

    public function down(): void
    {
        Schema::dropIfExists('question_edits');

        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(
                'idx_questions_first_served',
            );

            $table->dropColumn([
                'first_served_at',
                'edit_version',
            ]);
        });
    }

    private function backfillFirstServedAt(): void
    {
        $firstServed = [];

        DB::table('assessment_sessions')
            ->select([
                'session_id',
                'started_at',
                'served_question_ids',
                'current_question_id',
            ])
            ->orderBy('session_id')
            ->chunk(
                250,
                function ($sessions) use (
                    &$firstServed,
                ): void {
                    foreach ($sessions as $session) {
                        $questionIds = $this->decodeQuestionIds(
                            $session->served_question_ids,
                        );

                        /*
                         * Include current_question_id as defensive
                         * protection for old Practice sessions.
                         */
                        if (
                            $session->current_question_id
                            !== null
                        ) {
                            $questionIds[] = (string)
                            $session->current_question_id;
                        }

                        $servedAt = (string) (
                            $session->started_at
                            ?? now()
                        );

                        foreach (
                            array_unique($questionIds) as $questionId
                        ) {
                            if (
                                ! isset(
                                    $firstServed[$questionId]
                                )
                                || $servedAt
                                < $firstServed[$questionId]
                            ) {
                                $firstServed[$questionId] = $servedAt;
                            }
                        }
                    }
                },
            );

        /*
         * There are currently only a small number of questions,
         * so one update per unique question is reasonable for
         * this one-time migration.
         */
        foreach (
            $firstServed as $questionId => $servedAt
        ) {
            DB::table('questions')
                ->where(
                    'question_id',
                    $questionId,
                )
                ->update([
                    'first_served_at' => $servedAt,
                ]);
        }
    }

    /**
     * @return array<int, string>
     */
    private function decodeQuestionIds(
        mixed $value,
    ): array {
        if ($value === null || $value === '') {
            return [];
        }

        $decoded = is_string($value)
            ? json_decode(
                $value,
                true,
                512,
                JSON_THROW_ON_ERROR,
            )
            : $value;

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(
            array_filter(
                array_map(
                    fn (mixed $id): string => trim(
                        (string) $id,
                    ),
                    $decoded,
                ),
            ),
        );
    }
};
