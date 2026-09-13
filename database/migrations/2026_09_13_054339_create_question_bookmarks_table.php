<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_bookmarks', function (Blueprint $table) {
            $table->uuid('bookmark_id')->primary();
            $table->uuid('user_id');
            $table->uuid('question_id');
            $table->text('personal_notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'question_id'], 'uq_user_question_bookmark');

            $table->foreign('user_id')
                  ->references('user_id')
                  ->on('users')
                  ->restrictOnDelete()
                  ->cascadeOnUpdate();

            $table->foreign('question_id')
                  ->references('question_id')
                  ->on('questions')
                  ->restrictOnDelete()
                  ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_bookmarks');
    }
};