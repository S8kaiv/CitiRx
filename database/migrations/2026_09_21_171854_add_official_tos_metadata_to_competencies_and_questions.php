<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tos_competencies', function (Blueprint $table) {
            $table->decimal(
                'tos_weight_percentage',
                5,
                2
            )->default(0)->after('order_index');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->string(
                'research_form',
                30
            )->nullable()->after('is_diagnostic_pool');

            $table->unsignedSmallInteger(
                'form_position'
            )->nullable()->after('research_form');

            $table->string(
                'cognitive_level',
                20
            )->nullable()->after('form_position');

            $table->unique(
                ['research_form', 'form_position'],
                'uq_questions_research_form_position'
            );

            $table->index(
                ['research_form', 'is_active'],
                'idx_questions_research_form_active'
            );
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropUnique(
                'uq_questions_research_form_position'
            );

            $table->dropIndex(
                'idx_questions_research_form_active'
            );

            $table->dropColumn([
                'research_form',
                'form_position',
                'cognitive_level',
            ]);
        });

        Schema::table('tos_competencies', function (Blueprint $table) {
            $table->dropColumn('tos_weight_percentage');
        });
    }
};
