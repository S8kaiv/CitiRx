<?php

namespace Database\Seeders;

use App\Models\Cohort;
use App\Models\Level;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Pull dependent records (must exist: CohortSeeder + TierAndLevelSeeder ran first)
        $cohort = Cohort::where('cohort_name', 'BS Pharmacy Batch 2026')->first();
        $level1 = Level::where('level_number', 1)->first();

        if (! $cohort || ! $level1) {
            $this->command->error('✗ UserSeeder: missing cohort or level. Run CohortSeeder + TierAndLevelSeeder first.');
            return;
        }

        // ---- Admin ----
        User::updateOrCreate(
            ['email' => 'admin@citirx.test'],
            [
                'first_name'              => 'System',
                'last_name'               => 'Administrator',
                'password'                => 'password',
                'role'                    => 'admin',
                'email_verified_at'       => now(),
                'is_diagnostic_completed' => true,
                'cohort_id'               => null,
                'current_level'           => null,
            ]
        );

        // ---- Faculty ----
        User::updateOrCreate(
            ['email' => 'faculty@citirx.test'],
            [
                'first_name'              => 'Maria',
                'last_name'               => 'Santos',
                'password'                => 'password',
                'role'                    => 'faculty',
                'email_verified_at'       => now(),
                'is_diagnostic_completed' => true,
                'cohort_id'               => null,
                'current_level'           => null,
            ]
        );

        // ---- 5 Students ----
        $students = [
            ['first' => 'Juan',    'last' => 'Dela Cruz',  'email' => 'juan@citirx.test',   'sid' => '2021-00001'],
            ['first' => 'Ana',     'last' => 'Reyes',      'email' => 'ana@citirx.test',    'sid' => '2021-00002'],
            ['first' => 'Carlos',  'last' => 'Mendoza',    'email' => 'carlos@citirx.test', 'sid' => '2021-00003'],
            ['first' => 'Bea',     'last' => 'Garcia',     'email' => 'bea@citirx.test',    'sid' => '2021-00004'],
            ['first' => 'Diego',   'last' => 'Ramos',      'email' => 'diego@citirx.test',  'sid' => '2021-00005'],
        ];

        foreach ($students as $s) {
            User::updateOrCreate(
                ['email' => $s['email']],
                [
                    'first_name'              => $s['first'],
                    'last_name'               => $s['last'],
                    'student_id'              => $s['sid'],
                    'password'                => 'password',
                    'role'                    => 'student',
                    'email_verified_at'       => now(),
                    'is_diagnostic_completed' => false,
                    'cohort_id'               => $cohort->cohort_id,
                    'current_level'           => $level1->level_number,
                    'total_xp'                => 0,
                    'streak_count'            => 0,
                ]
            );
        }

        $this->command->info('✓ User seeder: 7 CitiRx test users processed (1 admin, 1 faculty, 5 students).');
    }
}