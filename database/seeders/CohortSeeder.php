<?php

namespace Database\Seeders;

use App\Models\Cohort;
use Illuminate\Database\Seeder;

class CohortSeeder extends Seeder
{
    public function run(): void
    {
        Cohort::updateOrCreate(
            [
                'cohort_name'   => 'BS Pharmacy Batch 2026',
                'academic_year' => '2025-2026',
            ],
            [
                'target_phle_date' => '2026-11-15',
            ]
        );

        $this->command->info('✓ Cohort seeder: 1 cohort created.');
    }
}