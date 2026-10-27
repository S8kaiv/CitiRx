<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'admin_audit_logs',
            function (Blueprint $table): void {
                $table->string('actor_name', 255)
                    ->nullable()
                    ->after('admin_id');

                $table->index(
                    'created_at',
                    'idx_admin_audit_created_at',
                );
            },
        );

        DB::table('users')
            ->select([
                'user_id',
                'first_name',
                'middle_name',
                'last_name',
            ])
            ->orderBy('user_id')
            ->each(function (object $user): void {
                $name = collect([
                    $user->first_name,
                    $user->middle_name,
                    $user->last_name,
                ])->filter()->implode(' ');

                DB::table('admin_audit_logs')
                    ->where('admin_id', $user->user_id)
                    ->whereNull('actor_name')
                    ->update([
                        'actor_name' => $name,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table(
            'admin_audit_logs',
            function (Blueprint $table): void {
                $table->dropIndex(
                    'idx_admin_audit_created_at',
                );

                $table->dropColumn('actor_name');
            },
        );
    }
};
