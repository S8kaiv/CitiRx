<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->timestamp('research_validated_at')
                ->nullable()
                ->after('cognitive_level');

            $table->uuid('research_validated_by')
                ->nullable()
                ->after('research_validated_at');

            $table->foreign('research_validated_by')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign([
                'research_validated_by',
            ]);

            $table->dropColumn([
                'research_validated_at',
                'research_validated_by',
            ]);
        });
    }
};
