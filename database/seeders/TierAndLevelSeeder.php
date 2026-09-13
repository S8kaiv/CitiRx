<?php

namespace Database\Seeders;

use App\Models\Level;
use App\Models\Tier;
use Illuminate\Database\Seeder;

class TierAndLevelSeeder extends Seeder
{
    public function run(): void
    {
        // Tiers (rank determines progression order)
        $tiers = [
            ['tier_name' => 'Beginner',     'tier_rank' => 1],
            ['tier_name' => 'Intermediate', 'tier_rank' => 2],
            ['tier_name' => 'Advanced',     'tier_rank' => 3],
            ['tier_name' => 'Expert',       'tier_rank' => 4],
        ];

        foreach ($tiers as $t) {
            Tier::updateOrCreate(
                ['tier_name' => $t['tier_name']],
                ['tier_rank' => $t['tier_rank']]
            );
        }

        // Map tier names to IDs
        $tierMap = Tier::pluck('tier_id', 'tier_name')->toArray();

        // Levels — each references its parent tier
        $levels = [
            // Beginner: levels 1–3
            ['level_number' => 1,  'min_xp' => 0,     'tier' => 'Beginner'],
            ['level_number' => 2,  'min_xp' => 100,   'tier' => 'Beginner'],
            ['level_number' => 3,  'min_xp' => 250,   'tier' => 'Beginner'],
            // Intermediate: levels 4–6
            ['level_number' => 4,  'min_xp' => 500,   'tier' => 'Intermediate'],
            ['level_number' => 5,  'min_xp' => 900,   'tier' => 'Intermediate'],
            ['level_number' => 6,  'min_xp' => 1500,  'tier' => 'Intermediate'],
            // Advanced: levels 7–8
            ['level_number' => 7,  'min_xp' => 2500,  'tier' => 'Advanced'],
            ['level_number' => 8,  'min_xp' => 4000,  'tier' => 'Advanced'],
            // Expert: levels 9–10
            ['level_number' => 9,  'min_xp' => 6500,  'tier' => 'Expert'],
            ['level_number' => 10, 'min_xp' => 10000, 'tier' => 'Expert'],
        ];

        foreach ($levels as $l) {
            Level::updateOrCreate(
                ['level_number' => $l['level_number']],
                [
                    'min_xp'  => $l['min_xp'],
                    'tier_id' => $tierMap[$l['tier']],
                ]
            );
        }

        $this->command->info('✓ Tier/Level seeder: 4 tiers, 10 levels created.');
    }
}