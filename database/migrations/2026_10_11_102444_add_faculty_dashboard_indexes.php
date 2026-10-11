<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->index(
                [
                    'cohort_id',
                    'role',
                    'deleted_at',
                ],
                'idx_users_cohort_role_deleted',
            );
        });

        Schema::table(
            'faculty_interventions',
            function (Blueprint $table): void {
                $table->index(
                    [
                        'faculty_id',
                        'assigned_at',
                    ],
                    'idx_interventions_faculty_assigned',
                );

                $table->index(
                    [
                        'faculty_id',
                        'target_user_id',
                        'is_completed',
                    ],
                    'idx_interventions_faculty_target_open',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(
                'idx_users_cohort_role_deleted',
            );
        });

        Schema::table(
            'faculty_interventions',
            function (Blueprint $table): void {
                $table->dropIndex(
                    'idx_interventions_faculty_assigned',
                );

                $table->dropIndex(
                    'idx_interventions_faculty_target_open',
                );
            },
        );
    }
};
