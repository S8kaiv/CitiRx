<?php

namespace Database\Seeders;

use App\Models\SurveyItem;
use Illuminate\Database\Seeder;

class SurveyItemSeeder extends Seeder
{
    public function run(): void
    {
        // 12 items across the 11 ISO/IEC 25010 characteristics
        // (usability gets 2 items since it's the primary UX measurement)
        $items = [
            ['code' => 'FS01', 'char' => 'functional_suitability', 'text' => 'The system provides all functions described in its specification.', 'order' => 1],
            ['code' => 'FS02', 'char' => 'functional_suitability', 'text' => 'The system produces accurate results when performing its functions.', 'order' => 2],
            ['code' => 'PE01', 'char' => 'performance_efficiency', 'text' => 'The system responds quickly to user input.', 'order' => 3],
            ['code' => 'CO01', 'char' => 'compatibility',          'text' => 'The system shares information with other systems as needed.', 'order' => 4],
            ['code' => 'USA01', 'char' => 'usability',             'text' => 'The system is easy to navigate and learn.', 'order' => 5],
            ['code' => 'USA02', 'char' => 'usability',             'text' => 'The system provides clear feedback when actions are performed.', 'order' => 6],
            ['code' => 'REL01', 'char' => 'reliability',           'text' => 'The system performs consistently without errors.', 'order' => 7],
            ['code' => 'SEC01', 'char' => 'security',              'text' => 'The system protects user data from unauthorized access.', 'order' => 8],
            ['code' => 'MNT01', 'char' => 'maintainability',       'text' => 'The system can be modified easily when requirements change.', 'order' => 9],
            ['code' => 'PRT01', 'char' => 'portability',           'text' => 'The system works across different devices and browsers.', 'order' => 10],
            ['code' => 'INT01', 'char' => 'interaction_capability','text' => 'The system interface is pleasant and appropriate for its purpose.', 'order' => 11],
            ['code' => 'FLX01', 'char' => 'flexibility',           'text' => 'The system adapts to changing user needs.', 'order' => 12],
        ];

        foreach ($items as $i) {
            SurveyItem::updateOrCreate(
                ['item_code' => $i['code']],
                [
                    'iso_characteristic' => $i['char'],
                    'item_text'          => $i['text'],
                    'order_index'        => $i['order'],
                ]
            );
        }

        $this->command->info('✓ SurveyItem seeder: 12 survey items processed.');
    }
}