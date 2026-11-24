<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'student')
            ->where('student_approval_pending', true)
            ->update([
                'email_verified_at' => null,
                'student_approval_pending' => false,
            ]);
    }

    public function down(): void
    {
        // A prior email verification timestamp cannot be reconstructed.
    }
};
