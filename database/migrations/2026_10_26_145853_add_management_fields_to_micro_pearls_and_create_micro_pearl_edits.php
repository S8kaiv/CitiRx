<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('micro_pearls', function (Blueprint $table): void {
            $table->uuid('created_by')
                ->nullable()
                ->after('competency_id');

            $table->timestamp('submitted_at')
                ->nullable()
                ->after('reviewed_at');

            $table->timestamp('retired_at')
                ->nullable()
                ->after('published_at');

            $table->unsignedInteger('edit_version')
                ->default(1)
                ->after('content_version');

            $table->foreign('created_by')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->index(
                ['content_status', 'submitted_at'],
                'idx_micro_pearls_review_queue',
            );
        });

        Schema::create('micro_pearl_edits', function (Blueprint $table): void {
            $table->uuid('edit_id')->primary();

            $table->uuid('pearl_id');
            $table->uuid('editor_id');

            $table->string('action', 40);

            $table->json('changes')->nullable();

            $table->text('reason')->nullable();

            $table->unsignedInteger('version_before');
            $table->unsignedInteger('version_after');

            $table->timestamp('edited_at')->useCurrent();

            $table->foreign('pearl_id')
                ->references('pearl_id')
                ->on('micro_pearls')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('editor_id')
                ->references('user_id')
                ->on('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->index(
                ['pearl_id', 'edited_at'],
                'idx_micro_pearl_edits_history',
            );

            $table->index(
                ['editor_id', 'edited_at'],
                'idx_micro_pearl_edits_editor',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('micro_pearl_edits');

        Schema::table('micro_pearls', function (Blueprint $table): void {
            $table->dropForeign(['created_by']);

            $table->dropIndex('idx_micro_pearls_review_queue');

            $table->dropColumn([
                'created_by',
                'submitted_at',
                'retired_at',
                'edit_version',
            ]);
        });
    }
};
