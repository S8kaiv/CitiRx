<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Order matters — parents before children
        $this->call([
            CohortSeeder::class,
            TierAndLevelSeeder::class,
            BadgeSeeder::class,
            TosSeeder::class,
            QuestionSeeder::class,
            SurveyItemSeeder::class,
            UserSeeder::class,   // last — depends on cohort + level
        ]);
        if (app()->environment('local', 'testing')) {
            $this->call(ResearchQuestionSeeder::class);
        }
    }
}
