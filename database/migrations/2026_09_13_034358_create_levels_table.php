<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('levels', function (Blueprint $table) {
            $table->unsignedSmallInteger('level_number')->primary();
            $table->unsignedInteger('min_xp')->unique();
            $table->unsignedTinyInteger('tier_id');

            $table->foreign('tier_id')
                  ->references('tier_id')
                  ->on('tiers')
                  ->restrictOnDelete()
                  ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('levels');
    }
};