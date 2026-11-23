<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practice_question_imports', function (Blueprint $table): void {
            $table->uuid('import_id')->primary();
            $table->foreignUuid('uploader_id')->constrained('users', 'user_id')->restrictOnDelete();
            $table->string('original_filename');
            $table->json('preview_rows')->nullable();
            $table->unsignedSmallInteger('row_count');
            $table->timestamp('expires_at')->index();
            $table->timestamp('committed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practice_question_imports');
    }
};
