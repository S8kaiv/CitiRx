<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cohorts', function (Blueprint $table) {
            $table->uuid('cohort_id')->primary();
            $table->string('cohort_name', 100);
            $table->string('academic_year', 20);
            $table->date('target_phle_date')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['cohort_name', 'academic_year'], 'uq_cohort_name_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cohorts');
    }
};