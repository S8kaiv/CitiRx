<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'research_form_imports',
            function (Blueprint $table): void {
                $table->uuid('import_id')->primary();

                $table->uuid('admin_id');
                $table->uuid('form_version_id');
                $table->uuid('validator_id')->nullable();

                $table->string('import_type', 20);
                $table->string('status', 20)
                    ->default('previewed');

                $table->string(
                    'original_filename',
                    255,
                );

                /*
                 * The path points only to server-generated private
                 * storage. It must never come from request input.
                 */
                $table->string(
                    'storage_path',
                    500,
                )->nullable();

                $table->char('file_sha256', 64);

                $table->unsignedBigInteger(
                    'file_size',
                );

                $table->unsignedInteger(
                    'expected_form_lock_version',
                );

                $table->unsignedInteger(
                    'lock_version',
                )->default(1);

                /*
                 * Command output contains validation summaries only.
                 * It must still be escaped when displayed.
                 */
                $table->mediumText(
                    'preview_output',
                )->nullable();

                $table->text(
                    'failure_message',
                )->nullable();

                $table->text('reason')->nullable();

                $table->timestamp('expires_at');
                $table->timestamp(
                    'committed_at',
                )->nullable();

                $table->timestamps();

                $table->foreign('admin_id')
                    ->references('user_id')
                    ->on('users')
                    ->restrictOnDelete()
                    ->cascadeOnUpdate();

                $table->foreign('form_version_id')
                    ->references('form_version_id')
                    ->on('research_form_versions')
                    ->restrictOnDelete()
                    ->cascadeOnUpdate();

                $table->foreign('validator_id')
                    ->references('user_id')
                    ->on('users')
                    ->restrictOnDelete()
                    ->cascadeOnUpdate();

                $table->index(
                    ['admin_id', 'status'],
                    'idx_research_import_owner_status',
                );

                $table->index(
                    ['status', 'expires_at'],
                    'idx_research_import_status_expiry',
                );

                $table->index(
                    'form_version_id',
                    'idx_research_import_version',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'research_form_imports',
        );
    }
};
