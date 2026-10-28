<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'cohort_assignment_imports',
            function (Blueprint $table): void {
                $table->uuid('import_id')->primary();

                $table->uuid('admin_id');

                $table->string(
                    'original_filename',
                    255,
                );

                $table->char(
                    'file_sha256',
                    64,
                );

                /*
                 * Contains only internal IDs and role/cohort state.
                 * Raw email and student-ID values are not retained.
                 */
                $table->json('assignment_rows');

                $table->json('summary');

                $table->unsignedInteger(
                    'lock_version',
                )->default(1);

                $table->timestamp('expires_at');

                $table->timestamp(
                    'committed_at',
                )->nullable();

                $table->timestamp(
                    'created_at',
                )->useCurrent();

                $table->foreign('admin_id')
                    ->references('user_id')
                    ->on('users')
                    ->restrictOnDelete()
                    ->cascadeOnUpdate();

                $table->index(
                    ['admin_id', 'expires_at'],
                    'idx_cohort_import_owner_expiry',
                );

                $table->index(
                    'committed_at',
                    'idx_cohort_import_committed',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'cohort_assignment_imports',
        );
    }
};
