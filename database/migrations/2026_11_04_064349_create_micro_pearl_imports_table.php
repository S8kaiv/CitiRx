<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'micro_pearl_imports',
            function (Blueprint $table): void {
                $table->uuid('import_id')->primary();

                $table->uuid('uploader_id');

                $table->string('status', 20)
                    ->default('previewed');

                $table->string(
                    'original_filename',
                    255,
                );

                /*
                 * Private, server-generated path.
                 * Never expose this path to the browser.
                 */
                $table->string(
                    'storage_path',
                    500,
                )->nullable();

                $table->char('file_sha256', 64);

                $table->unsignedBigInteger('file_size');

                $table->unsignedSmallInteger('row_count');

                /*
                 * Contains preview metadata only:
                 * row, competency, title, order and status.
                 * Full lesson content remains in the private CSV.
                 */
                $table->json('preview_rows');

                $table->unsignedInteger('lock_version')
                    ->default(1);

                $table->text('reason')->nullable();

                $table->text(
                    'failure_message',
                )->nullable();

                $table->timestamp('expires_at');

                $table->timestamp(
                    'committed_at',
                )->nullable();

                $table->timestamps();

                $table->foreign('uploader_id')
                    ->references('user_id')
                    ->on('users')
                    ->restrictOnDelete()
                    ->cascadeOnUpdate();

                $table->index(
                    ['uploader_id', 'status'],
                    'idx_pearl_import_owner_status',
                );

                $table->index(
                    ['status', 'expires_at'],
                    'idx_pearl_import_status_expiry',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'micro_pearl_imports',
        );
    }
};
