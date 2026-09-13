<?php

namespace Database\Seeders;

use App\Models\TosCompetency;
use App\Models\TosDomain;
use Illuminate\Database\Seeder;

class TosSeeder extends Seeder
{
    public function run(): void
    {
        // 6 PhLE Table of Specifications domains
        // PRC weights are placeholders — confirm against official PRC bulletin
        $domains = [
            [
                'domain_number'         => 1,
                'domain_name'           => 'Pharmaceutical Chemistry',
                'prc_weight_percentage' => 20.00,
                'competencies' => [
                    ['code' => 'PC-01', 'title' => 'Inorganic Pharmaceutical Chemistry', 'order' => 1],
                    ['code' => 'PC-02', 'title' => 'Organic Pharmaceutical Chemistry', 'order' => 2],
                ],
            ],
            [
                'domain_number'         => 2,
                'domain_name'           => 'Pharmacognosy',
                'prc_weight_percentage' => 10.00,
                'competencies' => [
                    ['code' => 'PG-01', 'title' => 'Crude Drug Identification', 'order' => 1],
                    ['code' => 'PG-02', 'title' => 'Phytochemistry and Plant Constituents', 'order' => 2],
                ],
            ],
            [
                'domain_number'         => 3,
                'domain_name'           => 'Practice of Pharmacy',
                'prc_weight_percentage' => 20.00,
                'competencies' => [
                    ['code' => 'PP-01', 'title' => 'Pharmaceutical Jurisprudence and Ethics', 'order' => 1],
                    ['code' => 'PP-02', 'title' => 'Dispensing and Compounding', 'order' => 2],
                ],
            ],
            [
                'domain_number'         => 4,
                'domain_name'           => 'Pharmacology and Toxicology',
                'prc_weight_percentage' => 20.00,
                'competencies' => [
                    ['code' => 'PT-01', 'title' => 'General Pharmacology and Pharmacokinetics', 'order' => 1],
                    ['code' => 'PT-02', 'title' => 'Toxicology and Poison Management', 'order' => 2],
                ],
            ],
            [
                'domain_number'         => 5,
                'domain_name'           => 'Quality Assurance and Quality Control',
                'prc_weight_percentage' => 15.00,
                'competencies' => [
                    ['code' => 'QA-01', 'title' => 'Pharmaceutical Analysis and Instrumentation', 'order' => 1],
                    ['code' => 'QA-02', 'title' => 'Good Manufacturing Practices', 'order' => 2],
                ],
            ],
            [
                'domain_number'         => 6,
                'domain_name'           => 'Clinical and Hospital Pharmacy',
                'prc_weight_percentage' => 15.00,
                'competencies' => [
                    ['code' => 'CH-01', 'title' => 'Pharmacotherapy and Drug Monitoring', 'order' => 1],
                    ['code' => 'CH-02', 'title' => 'Hospital Pharmacy Operations', 'order' => 2],
                ],
            ],
        ];

        foreach ($domains as $d) {
            $domain = TosDomain::updateOrCreate(
                ['domain_number' => $d['domain_number']],
                [
                    'domain_name'           => $d['domain_name'],
                    'prc_weight_percentage' => $d['prc_weight_percentage'],
                ]
            );

            foreach ($d['competencies'] as $c) {
                TosCompetency::updateOrCreate(
                    ['competency_code' => $c['code']],
                    [
                        'domain_id' => $domain->domain_id,
                        'title' => $c['title'],
                        'order_index' => $c['order'],
                        'bkt_transition_p_t' => 0.1000,
                    ]
                );
            }
        }

        $this->command->info('✓ TOS seeder: ' . TosDomain::count() . ' domains, ' . TosCompetency::count() . ' competencies.');
    }
}