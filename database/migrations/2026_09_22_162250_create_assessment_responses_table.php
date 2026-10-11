<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'assessment_responses',
            function (Blueprint $table) {
                $table->uuid('response_id')
                    ->primary();

                $table->uuid('session_id');

                $table->uuid('question_id');

                $table->uuid('selected_choice_id')
                    ->nullable();

                $table->unsignedSmallInteger(
                    'item_position'
                );

                /*
                 * null means unanswered.
                 * false means answered incorrectly.
                 * true means answered correctly.
                 */
                $table->boolean('is_correct')
                    ->nullable();

                $table->timestamp('answered_at')
                    ->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'session_id',
                        'question_id',
                    ],
                    'uq_assessment_response_question',
                );

                $table->unique(
                    [
                        'session_id',
                        'item_position',
                    ],
                    'uq_assessment_response_position',
                );

                $table->foreign('session_id')
                    ->references('session_id')
                    ->on('assessment_sessions')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();

                $table->foreign('question_id')
                    ->references('question_id')
                    ->on('questions')
                    ->restrictOnDelete()
                    ->cascadeOnUpdate();

                $table->foreign(
                    'selected_choice_id'
                )
                    ->references('choice_id')
                    ->on('question_choices')
                    ->restrictOnDelete()
                    ->cascadeOnUpdate();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'assessment_responses'
        );
    }
};
