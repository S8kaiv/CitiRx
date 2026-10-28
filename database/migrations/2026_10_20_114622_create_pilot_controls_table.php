<?php

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
            'pilot_controls',
            function (Blueprint $table): void {
                $table->uuid('pilot_control_id')->primary();

                $table->string('phase', 30)
                    ->default('setup');

                $table->timestamp(
                    'official_collection_started_at',
                )->nullable();

                $table->foreignUuid(
                    'official_collection_started_by',
                )
                    ->nullable()
                    ->constrained(
                        table: 'users',
                        column: 'user_id',
                    )
                    ->restrictOnDelete();

                $table->unsignedInteger('lock_version')
                    ->default(1);

                $table->timestamps();
            },
        );

        DB::table('pilot_controls')->insert([
            'pilot_control_id' => (string) Str::uuid(),
            'phase' => 'setup',
            'official_collection_started_at' => null,
            'official_collection_started_by' => null,
            'lock_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('pilot_controls');
    }
};
