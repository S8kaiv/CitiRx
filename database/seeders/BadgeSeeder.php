<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            [
                'badge_code'    => 'first_session',
                'badge_name'    => 'First Step',
                'description'   => 'Completed your first review session.',
                'criteria_json' => ['type' => 'sessions_completed', 'count' => 1],
            ],
            [
                'badge_code'    => 'streak_7',
                'badge_name'    => '7-Day Streak',
                'description'   => 'Reviewed 7 days in a row.',
                'criteria_json' => ['type' => 'streak', 'days' => 7],
            ],
            [
                'badge_code'    => 'streak_30',
                'badge_name'    => 'Monthly Grind',
                'description'   => 'Reviewed 30 days in a row.',
                'criteria_json' => ['type' => 'streak', 'days' => 30],
            ],
            [
                'badge_code'    => 'diagnostic_complete',
                'badge_name'    => 'Baseline Set',
                'description'   => 'Completed the diagnostic test.',
                'criteria_json' => ['type' => 'diagnostic_completed'],
            ],
            [
                'badge_code'    => 'first_mastery',
                'badge_name'    => 'Domain Mastered',
                'description'   => 'Achieved mastery (≥ 85%) in any competency.',
                'criteria_json' => ['type' => 'competency_mastery', 'threshold' => 0.85],
            ],
            [
                'badge_code'    => 'all_domains_touched',
                'badge_name'    => 'Well-Rounded',
                'description'   => 'Practiced at least one question in all 6 PhLE domains.',
                'criteria_json' => ['type' => 'domains_practiced', 'count' => 6],
            ],
        ];

        foreach ($badges as $b) {
            Badge::updateOrCreate(
                ['badge_code' => $b['badge_code']],
                $b
            );
        }

        $this->command->info('✓ Badge seeder: 6 badges created.');
    }
}