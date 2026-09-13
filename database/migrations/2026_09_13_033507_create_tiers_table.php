<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tiers', function (Blueprint $table) {
            $table->tinyIncrements('tier_id');
            $table->string('tier_name', 50)->unique();
            $table->unsignedTinyInteger('tier_rank')->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tiers');
    }
};