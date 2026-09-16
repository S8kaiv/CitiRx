<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_sessions', function (Blueprint $table) {
            $table->json('served_question_ids')
                ->nullable()
                ->after('research_phase');
        });
    }

    public function down(): void
    {
        Schema::table('assessment_sessions', function (Blueprint $table) {
            $table->dropColumn('served_question_ids');
        });
    }
};
