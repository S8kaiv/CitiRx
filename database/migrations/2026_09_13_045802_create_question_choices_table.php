<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_choices', function (Blueprint $table) {
            $table->uuid('choice_id')->primary();
            $table->uuid('question_id');
            $table->char('choice_letter', 1);
            $table->text('choice_text');
            $table->boolean('is_correct')->default(false);
            $table->text('distractor_fallacy_note')->nullable();

            $table->unique(['question_id', 'choice_letter'], 'uq_question_choice_letter');

            $table->foreign('question_id')
                  ->references('question_id')
                  ->on('questions')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();
        });

        DB::statement(
            "ALTER TABLE question_choices ADD CONSTRAINT chk_choice_letter "
            . "CHECK (choice_letter IN ('A','B','C','D'))"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('question_choices');
    }
};