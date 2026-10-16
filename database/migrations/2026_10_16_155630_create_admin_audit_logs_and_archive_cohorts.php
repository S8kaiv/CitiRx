<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cohorts', function (Blueprint $table): void {
            $table->timestamp('archived_at')
                ->nullable()
                ->after('post_test_closes_at');

            $table->index(
                'archived_at',
                'idx_cohorts_archived_at',
            );
        });

        Schema::create('admin_audit_logs', function (Blueprint $table): void {
            $table->uuid('log_id')->primary();

            $table->uuid('admin_id');

            $table->string('action', 80);

            $table->string('target_type', 100);

            $table->uuid('target_id');

            $table->json('changes')->nullable();

            $table->string('reason', 500)->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->foreign('admin_id')
                ->references('user_id')
                ->on('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->index(
                ['target_type', 'target_id', 'created_at'],
                'idx_admin_audit_target',
            );

            $table->index(
                ['admin_id', 'created_at'],
                'idx_admin_audit_actor',
            );

            $table->index(
                ['action', 'created_at'],
                'idx_admin_audit_action',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');

        Schema::table('cohorts', function (Blueprint $table): void {
            $table->dropIndex('idx_cohorts_archived_at');
            $table->dropColumn('archived_at');
        });
    }
};
