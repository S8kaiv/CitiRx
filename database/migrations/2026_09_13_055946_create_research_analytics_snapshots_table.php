<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_analytics_snapshots', function (Blueprint $table) {
            $table->uuid('snapshot_id')->primary();
            $table->uuid('cohort_id');
            $table->date('snapshot_date');
            $table->unsignedInteger('cohort_size');
            $table->decimal('cohort_mean_predicted_readiness_pct', 5, 2)->nullable();
            $table->decimal('projected_pass_rate_pct', 5, 2)->nullable();
            $table->decimal('speed_floor_compliance_pct', 5, 2)->nullable();
            $table->decimal('bkt_mae', 6, 4)->nullable();
            $table->decimal('bkt_rmse', 6, 4)->nullable();
            $table->decimal('wilcoxon_w_statistic', 8, 2)->nullable();
            $table->decimal('wilcoxon_p_value', 5, 4)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['cohort_id', 'snapshot_date'], 'uq_cohort_snapshot_date');

            $table->foreign('cohort_id')
                  ->references('cohort_id')
                  ->on('cohorts')
                  ->restrictOnDelete()
                  ->cascadeOnUpdate();
        });

        DB::statement(
            'ALTER TABLE research_analytics_snapshots ADD CONSTRAINT chk_pass_rate '
            . 'CHECK (projected_pass_rate_pct IS NULL OR projected_pass_rate_pct BETWEEN 0 AND 100)'
        );

        DB::statement(
            'ALTER TABLE research_analytics_snapshots ADD CONSTRAINT chk_speed_floor '
            . 'CHECK (speed_floor_compliance_pct IS NULL OR speed_floor_compliance_pct BETWEEN 0 AND 100)'
        );

        DB::statement(
            'ALTER TABLE research_analytics_snapshots ADD CONSTRAINT chk_wilcoxon_p '
            . 'CHECK (wilcoxon_p_value IS NULL OR wilcoxon_p_value BETWEEN 0 AND 1)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('research_analytics_snapshots');
    }
};