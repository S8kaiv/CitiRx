<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->enum('role', ['student', 'faculty', 'admin', 'dean'])
                ->default('student')
                ->change();
        });

        Schema::table('questions', function (Blueprint $table): void {
            $table->text('source_reference')->nullable();
            $table->string('copyright_permission', 32)->nullable();
        });
    }

    public function down(): void
    {
        if (DB::table('users')->where('role', 'dean')->exists()) {
            throw new RuntimeException('Reassign dean accounts before rolling back this migration.');
        }

        Schema::table('questions', function (Blueprint $table): void {
            $table->dropColumn(['source_reference', 'copyright_permission']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->enum('role', ['student', 'faculty', 'admin'])
                ->default('student')
                ->change();
        });
    }
};
