<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('micro_pearls', function (Blueprint $table) {
            $table->uuid('pearl_id')->primary();
            $table->uuid('competency_id');
            $table->string('title', 200);
            $table->text('mnemonic_summary');
            $table->unsignedInteger('estimated_read_seconds')->default(0);
            $table->unsignedInteger('order_index')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['competency_id', 'order_index'], 'uq_competency_pearl_order');

            $table->foreign('competency_id')
                  ->references('competency_id')
                  ->on('tos_competencies')
                  ->restrictOnDelete()
                  ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('micro_pearls');
    }
};