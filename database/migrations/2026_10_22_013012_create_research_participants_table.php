<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'research_participants',
            function (Blueprint $table): void {
                $table->uuid('research_participant_id')->primary();

                $table->foreignUuid('user_id')
                    ->unique()
                    ->constrained(
                        table: 'users',
                        column: 'user_id',
                    )
                    ->restrictOnDelete();

                $table->string('status', 24)->index();
                $table->text('reason');

                $table->foreignUuid('changed_by')
                    ->constrained(
                        table: 'users',
                        column: 'user_id',
                    )
                    ->restrictOnDelete();

                $table->timestamp('changed_at');
                $table->unsignedInteger('lock_version')->default(1);
                $table->timestamps();

                $table->index(
                    ['status', 'changed_at'],
                    'idx_research_participants_status_changed',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('research_participants');
    }
};
