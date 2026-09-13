<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->uuid('response_id')->primary();
            $table->uuid('evaluation_id');
            $table->uuid('item_id');
            $table->unsignedTinyInteger('likert_score');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['evaluation_id', 'item_id'], 'uq_eval_item');

            $table->foreign('evaluation_id')
                  ->references('evaluation_id')
                  ->on('iso_survey_evaluations')
                  ->cascadeOnDelete()      // responses belong to the evaluation
                  ->cascadeOnUpdate();

            $table->foreign('item_id')
                  ->references('item_id')
                  ->on('survey_items')
                  ->restrictOnDelete()      // items are master data
                  ->cascadeOnUpdate();
        });

        DB::statement(
            'ALTER TABLE survey_responses ADD CONSTRAINT chk_likert '
            . 'CHECK (likert_score BETWEEN 1 AND 5)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};