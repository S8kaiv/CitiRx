<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tos_competencies', function (Blueprint $table) {
            $table->uuid('competency_id')->primary();
            $table->unsignedInteger('domain_id');
            $table->string('competency_code', 50)->unique();
            $table->string('title', 255);
            $table->unsignedInteger('order_index')->default(0);
            $table->decimal('bkt_transition_p_t', 5, 4)->default(0.1000);

            $table->unique(['domain_id', 'order_index'], 'uq_domain_competency_order');

            $table->foreign('domain_id')
                  ->references('domain_id')
                  ->on('tos_domains')
                  ->restrictOnDelete()
                  ->cascadeOnUpdate();
        });

        DB::statement(
            'ALTER TABLE tos_competencies ADD CONSTRAINT chk_p_t '
            . 'CHECK (bkt_transition_p_t BETWEEN 0 AND 1)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('tos_competencies');
    }
};