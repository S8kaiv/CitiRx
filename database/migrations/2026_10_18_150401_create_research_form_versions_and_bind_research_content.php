<?php

use App\Models\Question;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'research_form_versions',
            function (Blueprint $table): void {
                $table
                    ->uuid('form_version_id')
                    ->primary();

                $table->string(
                    'research_form',
                    30,
                );

                $table->unsignedSmallInteger(
                    'version_number',
                );

                $table
                    ->string('version_code', 80)
                    ->unique();

                $table
                    ->string('status', 30)
                    ->default('draft');

                $table
                    ->boolean('is_active')
                    ->default(false);

                /*
                 * MySQL permits multiple NULL values in a unique
                 * column. An active row stores its research_form here,
                 * guaranteeing only one active version per form.
                 */
                $table
                    ->string('active_slot', 30)
                    ->nullable()
                    ->unique();

                $table
                    ->foreignUuid('created_by')
                    ->nullable()
                    ->constrained(
                        table: 'users',
                        column: 'user_id',
                    )
                    ->restrictOnDelete();

                $table
                    ->foreignUuid('published_by')
                    ->nullable()
                    ->constrained(
                        table: 'users',
                        column: 'user_id',
                    )
                    ->restrictOnDelete();

                $table->timestamp(
                    'published_at',
                )->nullable();

                $table->timestamp(
                    'first_served_at',
                )->nullable();

                /*
                 * Used for optimistic concurrency protection in 4B.
                 */
                $table
                    ->unsignedInteger('lock_version')
                    ->default(1);

                $table->timestamps();

                $table->unique(
                    [
                        'research_form',
                        'version_number',
                    ],
                    'uq_research_form_version_number',
                );

                $table->index(
                    [
                        'research_form',
                        'status',
                    ],
                    'idx_research_form_versions_status',
                );
            },
        );

        Schema::table(
            'questions',
            function (Blueprint $table): void {
                $table
                    ->uuid('form_version_id')
                    ->nullable()
                    ->after('research_form');
            },
        );

        Schema::table(
            'assessment_sessions',
            function (Blueprint $table): void {
                $table
                    ->uuid('form_version_id')
                    ->nullable()
                    ->after('exam_form_version');
            },
        );

        $this->backfillVersion(
            Question::FORM_PRE_TEST_A,
            'citirx.research.pre_test_form_version',
        );

        $this->backfillVersion(
            Question::FORM_POST_TEST_B,
            'citirx.research.post_test_form_version',
        );

        /*
         * The old unique key prevents position 1 from existing in
         * both v1 and v2. Replace it with a version-scoped key.
         */
        Schema::table(
            'questions',
            function (Blueprint $table): void {
                $table->dropUnique(
                    'uq_questions_research_form_position',
                );

                $table->foreign(
                    'form_version_id',
                    'fk_questions_form_version',
                )
                    ->references('form_version_id')
                    ->on('research_form_versions')
                    ->restrictOnDelete();

                $table->unique(
                    [
                        'form_version_id',
                        'form_position',
                    ],
                    'uq_questions_form_version_position',
                );

                $table->index(
                    [
                        'form_version_id',
                        'is_active',
                    ],
                    'idx_questions_form_version_active',
                );
            },
        );

        Schema::table(
            'assessment_sessions',
            function (Blueprint $table): void {
                $table->foreign(
                    'form_version_id',
                    'fk_sessions_form_version',
                )
                    ->references('form_version_id')
                    ->on('research_form_versions')
                    ->restrictOnDelete();

                $table->index(
                    'form_version_id',
                    'idx_sessions_form_version',
                );
            },
        );
    }

    public function down(): void
    {
        /*
         * Refuse a destructive rollback after additional versions
         * exist. Restoring the old unique key would otherwise fail or
         * could encourage deletion of research history.
         */
        $hasMultipleVersions = DB::table(
            'research_form_versions',
        )
            ->select('research_form')
            ->groupBy('research_form')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasMultipleVersions) {
            throw new RuntimeException(
                'Cannot roll back research-form versioning after '
                    .'additional versions have been created.',
            );
        }

        Schema::table(
            'assessment_sessions',
            function (Blueprint $table): void {
                $table->dropForeign(
                    'fk_sessions_form_version',
                );

                $table->dropIndex(
                    'idx_sessions_form_version',
                );

                $table->dropColumn(
                    'form_version_id',
                );
            },
        );

        Schema::table(
            'questions',
            function (Blueprint $table): void {
                $table->dropForeign(
                    'fk_questions_form_version',
                );

                $table->dropUnique(
                    'uq_questions_form_version_position',
                );

                $table->dropIndex(
                    'idx_questions_form_version_active',
                );

                $table->dropColumn(
                    'form_version_id',
                );

                $table->unique(
                    [
                        'research_form',
                        'form_position',
                    ],
                    'uq_questions_research_form_position',
                );
            },
        );

        Schema::dropIfExists(
            'research_form_versions',
        );
    }

    private function backfillVersion(
        string $form,
        string $configKey,
    ): void {
        $configuredCode = trim(
            (string) config(
                $configKey,
                "{$form}_v1",
            ),
        );

        $versionCode = $configuredCode !== ''
            ? $configuredCode
            : "{$form}_v1";

        $versionNumber = 1;

        if (
            preg_match(
                '/_v([1-9][0-9]*)$/',
                $versionCode,
                $matches,
            ) === 1
        ) {
            $versionNumber = (int) $matches[1];
        }

        $questions = DB::table('questions')
            ->where('research_form', $form)
            ->where('is_active', true);

        $questionCount = (clone $questions)->count();

        $validatedCount = (clone $questions)
            ->whereNotNull('research_validated_at')
            ->whereNotNull('research_validated_by')
            ->count();

        $firstServedAt = (clone $questions)
            ->whereNotNull('first_served_at')
            ->min('first_served_at');

        $status = match (true) {
            $firstServedAt !== null => 'published',

            $questionCount === 60
                && $validatedCount === 60 => 'validated',

            default => 'draft',
        };

        $versionId = (string) Str::uuid();
        $now = now();

        DB::table('research_form_versions')->insert([
            'form_version_id' => $versionId,
            'research_form' => $form,
            'version_number' => $versionNumber,
            'version_code' => $versionCode,
            'status' => $status,
            'is_active' => true,
            'active_slot' => $form,
            'created_by' => null,
            'published_by' => null,
            'published_at' => $firstServedAt,
            'first_served_at' => $firstServedAt,
            'lock_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('questions')
            ->where('research_form', $form)
            ->update([
                'form_version_id' => $versionId,
            ]);

        DB::table('assessment_sessions')
            ->where(
                'exam_form_version',
                $versionCode,
            )
            ->update([
                'form_version_id' => $versionId,
            ]);
    }
};
