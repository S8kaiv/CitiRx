<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badges', function (Blueprint $table) {
            $table->uuid('badge_id')->primary();
            $table->string('badge_code', 50)->unique();
            $table->string('badge_name', 100);
            $table->text('description');
            $table->string('icon_path', 255)->nullable();
            $table->json('criteria_json');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('badges');
    }
};