<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('micro_pearls', function (Blueprint $table): void {
            $table->string('slug', 220)
                ->nullable()
                ->after('competency_id');

            $table->longText('lesson_content')
                ->nullable()
                ->after('mnemonic_summary');

            $table->json('learning_objectives')
                ->nullable()
                ->after('lesson_content');

            $table->json('key_points')
                ->nullable()
                ->after('learning_objectives');

            $table->json('common_pitfalls')
                ->nullable()
                ->after('key_points');

            $table->json('recall_prompts')
                ->nullable()
                ->after('common_pitfalls');

            $table->json('source_references')
                ->nullable()
                ->after('recall_prompts');

            $table->string('content_status', 20)
                ->default('draft')
                ->after('order_index');

            $table->string('rights_status', 20)
                ->default('pending')
                ->after('content_status');

            $table->boolean('is_ai_generated')
                ->default(false)
                ->after('rights_status');

            $table->boolean('ai_enrichment_allowed')
                ->default(false)
                ->after('is_ai_generated');

            $table->json('generation_metadata')
                ->nullable()
                ->after('ai_enrichment_allowed');

            $table->unsignedSmallInteger('content_version')
                ->default(1)
                ->after('generation_metadata');

            $table->uuid('reviewed_by')
                ->nullable()
                ->after('content_version');

            $table->timestamp('reviewed_at')
                ->nullable()
                ->after('reviewed_by');

            $table->timestamp('published_at')
                ->nullable()
                ->after('reviewed_at');

            $table->timestamp('updated_at')
                ->nullable()
                ->after('created_at');

            $table->unique(
                'slug',
                'uq_micro_pearls_slug'
            );

            $table->index(
                [
                    'content_status',
                    'rights_status',
                    'published_at',
                ],
                'idx_micro_pearls_publication'
            );

            $table->foreign(
                'reviewed_by',
                'fk_micro_pearls_reviewed_by'
            )
                ->references('user_id')
                ->on('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('micro_pearls', function (Blueprint $table): void {
            $table->dropForeign(
                'fk_micro_pearls_reviewed_by'
            );

            $table->dropUnique(
                'uq_micro_pearls_slug'
            );

            $table->dropIndex(
                'idx_micro_pearls_publication'
            );

            $table->dropColumn([
                'slug',
                'lesson_content',
                'learning_objectives',
                'key_points',
                'common_pitfalls',
                'recall_prompts',
                'source_references',
                'content_status',
                'rights_status',
                'is_ai_generated',
                'ai_enrichment_allowed',
                'generation_metadata',
                'content_version',
                'reviewed_by',
                'reviewed_at',
                'published_at',
                'updated_at',
            ]);
        });
    }
};
