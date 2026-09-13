<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_badges', function (Blueprint $table) {
            $table->uuid('user_badge_id')->primary();
            $table->uuid('user_id');
            $table->uuid('badge_id');
            $table->timestamp('unlocked_at')->useCurrent();

            $table->unique(['user_id', 'badge_id'], 'uq_user_badge');

            $table->foreign('user_id')
                  ->references('user_id')
                  ->on('users')
                  ->restrictOnDelete()
                  ->cascadeOnUpdate();

            $table->foreign('badge_id')
                  ->references('badge_id')
                  ->on('badges')
                  ->restrictOnDelete()
                  ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_badges');
    }
};