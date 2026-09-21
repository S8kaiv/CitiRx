<?php

namespace Database\Seeders;

use App\Models\TosCompetency;
use App\Models\TosDomain;
use Illuminate\Database\Seeder;
use RuntimeException;

class TosSeeder extends Seeder
{
    public function run(): void
    {
        $domains = [
            [
                'number' => 1,
                'name' => 'Pharmaceutical Chemistry',
                'weight' => 20.00,
                'competencies' => [
                    [
                        'code' => 'PC-01',
                        'title' => 'Inorganic Pharmaceutical Chemistry',
                        'order' => 1,
                        'weight' => 45.00,
                    ],
                    [
                        'code' => 'PC-02',
                        'title' => 'Organic Pharmaceutical Chemistry',
                        'order' => 2,
                        'weight' => 45.00,
                    ],
                    [
                        'code' => 'PC-03',
                        'title' => 'Qualitative Pharmaceutical Chemistry',
                        'order' => 3,
                        'weight' => 10.00,
                    ],
                ],
            ],
            [
                'number' => 2,
                'name' => 'Pharmacognosy/Biochemistry',
                'weight' => 15.00,
                'competencies' => [
                    [
                        'code' => 'PB-01',
                        'title' => 'Biochemistry',
                        'order' => 1,
                        'weight' => 50.00,
                    ],
                    [
                        'code' => 'PB-02',
                        'title' => 'Pharmacognosy',
                        'order' => 2,
                        'weight' => 50.00,
                    ],
                ],
            ],
            [
                'number' => 3,
                'name' => 'Practice of Pharmacy',
                'weight' => 17.50,
                'competencies' => [
                    [
                        'code' => 'PP-01',
                        'title' => 'Compounding and Dispensing',
                        'order' => 1,
                        'weight' => 21.00,
                    ],
                    [
                        'code' => 'PP-02',
                        'title' => 'Clinical Pharmacy',
                        'order' => 2,
                        'weight' => 37.00,
                    ],
                    [
                        'code' => 'PP-03',
                        'title' => 'Hospital Pharmacy',
                        'order' => 3,
                        'weight' => 21.00,
                    ],
                    [
                        'code' => 'PP-04',
                        'title' => 'Pharmaceutical Calculations',
                        'order' => 4,
                        'weight' => 21.00,
                    ],
                ],
            ],
            [
                'number' => 4,
                'name' => 'Pharmacology-Pharmacokinetics',
                'weight' => 15.00,
                'competencies' => [
                    [
                        'code' => 'PK-01',
                        'title' => 'Pharmacology',
                        'order' => 1,
                        'weight' => 58.00,
                    ],
                    [
                        'code' => 'PK-02',
                        'title' => 'Pharmacokinetics',
                        'order' => 2,
                        'weight' => 21.00,
                    ],
                    [
                        'code' => 'PK-03',
                        'title' => 'Toxicology, Incompatibilities, and Adverse Drug Reactions',
                        'order' => 3,
                        'weight' => 21.00,
                    ],
                ],
            ],
            [
                'number' => 5,
                'name' => 'Pharmaceutics',
                'weight' => 17.50,
                'competencies' => [
                    [
                        'code' => 'PH-01',
                        'title' => 'Manufacturing Pharmacy',
                        'order' => 1,
                        'weight' => 29.00,
                    ],
                    [
                        'code' => 'PH-02',
                        'title' => 'Pharmaceutical Dosage Forms',
                        'order' => 2,
                        'weight' => 29.00,
                    ],
                    [
                        'code' => 'PH-03',
                        'title' => 'Physical Pharmacy',
                        'order' => 3,
                        'weight' => 25.00,
                    ],
                    [
                        'code' => 'PH-04',
                        'title' => 'Jurisprudence and Ethics',
                        'order' => 4,
                        'weight' => 17.00,
                    ],
                ],
            ],
            [
                'number' => 6,
                'name' => 'Quality Control/Quality Assurance',
                'weight' => 15.00,
                'competencies' => [
                    [
                        'code' => 'QC-01',
                        'title' => 'Quality Control: Drug Testing and Assaying',
                        'order' => 1,
                        'weight' => 25.00,
                    ],
                    [
                        'code' => 'QC-02',
                        'title' => 'Quality Control II with Instrumentation',
                        'order' => 2,
                        'weight' => 25.00,
                    ],
                    [
                        'code' => 'QC-03',
                        'title' => 'Pharmaceutical Microbiology and Parasitology',
                        'order' => 3,
                        'weight' => 25.00,
                    ],
                    [
                        'code' => 'QC-04',
                        'title' => 'Public Health',
                        'order' => 4,
                        'weight' => 25.00,
                    ],
                ],
            ],
        ];

        if (
            abs(
                array_sum(
                    array_column($domains, 'weight')
                ) - 100
            ) > 0.001
        ) {
            throw new RuntimeException(
                'Official subject weights must total 100%.'
            );
        }

        foreach ($domains as $definition) {
            $componentTotal = array_sum(
                array_column(
                    $definition['competencies'],
                    'weight'
                )
            );

            if (abs($componentTotal - 100) > 0.001) {
                throw new RuntimeException(
                    "{$definition['name']} component weights must total 100%."
                );
            }

            $domain = TosDomain::updateOrCreate(
                [
                    'domain_number' => $definition['number'],
                ],
                [
                    'domain_name' => $definition['name'],
                    'prc_weight_percentage' => $definition['weight'],
                ]
            );

            foreach ($definition['competencies'] as $competency) {
                TosCompetency::updateOrCreate(
                    [
                        'competency_code' => $competency['code'],
                    ],
                    [
                        'domain_id' => $domain->domain_id,
                        'title' => $competency['title'],
                        'order_index' => $competency['order'],
                        'tos_weight_percentage' => $competency['weight'],
                        'bkt_transition_p_t' => 0.1000,
                    ]
                );
            }
        }

        $this->command?->info(
            'TOS seeder: '
            .TosDomain::count()
            .' subjects, '
            .TosCompetency::count()
            .' components.'
        );
    }
}
