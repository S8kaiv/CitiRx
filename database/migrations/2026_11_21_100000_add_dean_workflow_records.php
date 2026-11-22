<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_name_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users', 'user_id');
            $table->json('original_names');
            $table->json('requested_names');
            $table->string('status', 20)->default('pending');
            $table->uuid('pending_user_id')->nullable()->unique();
            $table->foreignUuid('decided_by')->nullable()->constrained('users', 'user_id');
            $table->text('decision_note')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
        Schema::table('research_form_versions', function (Blueprint $table): void {
            $table->foreignUuid('dean_approved_by')->nullable()->constrained('users', 'user_id');
            $table->timestamp('dean_approved_at')->nullable();
            $table->string('approved_content_digest', 64)->nullable();
        });
        Schema::table('research_form_imports', function (Blueprint $table): void {
            $table->json('preview_rows')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('research_form_imports', fn (Blueprint $table) => $table->dropColumn('preview_rows'));
        Schema::table('research_form_versions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('dean_approved_by');
            $table->dropColumn(['dean_approved_at', 'approved_content_digest']);
        });
        Schema::dropIfExists('student_name_requests');
    }
};
