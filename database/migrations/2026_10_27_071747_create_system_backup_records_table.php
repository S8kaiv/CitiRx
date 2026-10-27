<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'system_backup_records',
            function (Blueprint $table): void {
                $table->uuid('backup_id')->primary();
                $table->string('status', 20);
                $table->unsignedBigInteger('size_bytes')->nullable();
                $table->char('checksum_sha256', 64)->nullable();
                $table->timestamp('completed_at');
                $table->timestamp('created_at')->useCurrent();

                $table->index(
                    ['status', 'completed_at'],
                    'idx_system_backups_status_completed',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('system_backup_records');
    }
};
