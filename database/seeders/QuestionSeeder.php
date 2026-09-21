<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\QuestionChoice;
use App\Models\TosCompetency;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        // Development-only Practice questions grouped by corrected TOS component.
        $questions = [
            'PC-01' => [
                [
                    'text' => 'Which halogen has the highest electronegativity?',
                    'rationale' => 'Electronegativity increases up and to the right of the periodic table. Fluorine is the most electronegative element.',
                    'choices' => [
                        'A' => ['Fluorine', true, null],
                        'B' => ['Chlorine', false, 'Less electronegative than fluorine.'],
                        'C' => ['Bromine', false, 'Less electronegative than fluorine.'],
                        'D' => ['Iodine', false, 'Least electronegative halogen.'],
                    ],
                ],
                [
                    'text' => 'Which of the following is a characteristic of ionic compounds?',
                    'rationale' => 'Ionic compounds are formed between metals and non-metals and typically have high melting points.',
                    'choices' => [
                        'A' => ['Low melting point', false, 'Typical of covalent compounds.'],
                        'B' => ['High melting point', true, null],
                        'C' => ['Poor electrical conductivity', false, 'Ionic compounds conduct when dissolved.'],
                        'D' => ['Insoluble in water', false, 'Most ionic compounds dissolve in water.'],
                    ],
                ],
            ],

            'PC-02' => [
                [
                    'text' => 'Which functional group is present in a carboxylic acid?',
                    'rationale' => 'Carboxylic acids contain the -COOH functional group.',
                    'choices' => [
                        'A' => ['-OH', false, 'This is an alcohol.'],
                        'B' => ['-CHO', false, 'This is an aldehyde.'],
                        'C' => ['-COOH', true, null],
                        'D' => ['-NH2', false, 'This is an amine.'],
                    ],
                ],
                [
                    'text' => 'Which class of organic compound contains a carbonyl group bonded to two carbon atoms?',
                    'rationale' => 'A ketone has a carbonyl group (C=O) bonded to two carbon-containing groups.',
                    'choices' => [
                        'A' => ['Aldehyde', false, 'Carbonyl bonded to at least one hydrogen.'],
                        'B' => ['Ketone', true, null],
                        'C' => ['Ester', false, 'Carbonyl bonded to an oxygen.'],
                        'D' => ['Amide', false, 'Carbonyl bonded to a nitrogen.'],
                    ],
                ],
            ],

            'PB-02' => [
                [
                    'text' => 'Which part of the Cinchona plant is the primary source of quinine?',
                    'rationale' => 'Quinine is extracted from the bark of Cinchona trees.',
                    'choices' => [
                        'A' => ['Leaves', false, 'Not the primary source.'],
                        'B' => ['Bark', true, null],
                        'C' => ['Roots', false, 'Not the primary source.'],
                        'D' => ['Flowers', false, 'Not the primary source.'],
                    ],
                ],
                [
                    'text' => 'Which alkaloid is the primary active constituent of Papaver somniferum?',
                    'rationale' => 'Morphine is the principal alkaloid from opium poppy.',
                    'choices' => [
                        'A' => ['Atropine', false, 'From Atropa belladonna.'],
                        'B' => ['Morphine', true, null],
                        'C' => ['Quinine', false, 'From Cinchona bark.'],
                        'D' => ['Reserpine', false, 'From Rauwolfia serpentina.'],
                    ],
                ],
                [
                    'text' => 'Which phytochemical test detects the presence of tannins?',
                    'rationale' => 'Ferric chloride produces a blue-black or green color with tannins.',
                    'choices' => [
                        'A' => ['Ferric chloride test', true, null],
                        'B' => ['Molisch test', false, 'Detects carbohydrates.'],
                        'C' => ['Ninhydrin test', false, 'Detects amino acids.'],
                        'D' => ['Liebermann test', false, 'Detects steroids.'],
                    ],
                ],
                [
                    'text' => 'Which plant constituent is responsible for the red color of tomatoes?',
                    'rationale' => 'Lycopene is a carotenoid pigment responsible for the red color of tomatoes.',
                    'choices' => [
                        'A' => ['Chlorophyll', false, 'Green pigment.'],
                        'B' => ['Lycopene', true, null],
                        'C' => ['Anthocyanin', false, 'Blue/purple pigment.'],
                        'D' => ['Carotene', false, 'Orange pigment.'],
                    ],
                ],
            ],

            'PP-01' => [
                [
                    'text' => 'What does the "Rx" symbol on a prescription indicate?',
                    'rationale' => 'Rx is the traditional symbol for a prescription, derived from the Latin "recipe" (take thou).',
                    'choices' => [
                        'A' => ['A controlled substance', false, 'Rx alone does not indicate control.'],
                        'B' => ['A prescription order', true, null],
                        'C' => ['A refillable drug', false, 'Refill status is a separate notation.'],
                        'D' => ['A generic substitute', false, 'Generic substitution has its own rules.'],
                    ],
                ],
                [
                    'text' => 'What is the primary purpose of trituration in compounding?',
                    'rationale' => 'Trituration reduces particle size and ensures uniform mixing of powders.',
                    'choices' => [
                        'A' => ['Increase particle size', false, 'Opposite of trituration.'],
                        'B' => ['Reduce particle size', true, null],
                        'C' => ['Sterilize the powder', false, 'Sterilization is a separate step.'],
                        'D' => ['Dissolve the powder', false, 'Dissolution is a separate process.'],
                    ],
                ],
            ],

            'PP-02' => [
                [
                    'text' => 'Which laboratory test is used to monitor warfarin therapy?',
                    'rationale' => 'INR (International Normalized Ratio) is used to monitor warfarin\'s anticoagulant effect.',
                    'choices' => [
                        'A' => ['aPTT', false, 'Monitors heparin.'],
                        'B' => ['INR', true, null],
                        'C' => ['BUN', false, 'Renal function.'],
                        'D' => ['HbA1c', false, 'Glycemic control.'],
                    ],
                ],
                [
                    'text' => 'Which drug requires therapeutic drug monitoring due to a narrow therapeutic index?',
                    'rationale' => 'Digoxin has a narrow therapeutic index and requires TDM to avoid toxicity.',
                    'choices' => [
                        'A' => ['Paracetamol', false, 'Wide therapeutic index.'],
                        'B' => ['Digoxin', true, null],
                        'C' => ['Amoxicillin', false, 'Wide therapeutic index.'],
                        'D' => ['Cetirizine', false, 'Wide therapeutic index.'],
                    ],
                ],
            ],

            'PP-03' => [
                [
                    'text' => 'Which committee in a hospital is responsible for overseeing drug use and policies?',
                    'rationale' => 'The Pharmacy and Therapeutics (P&T) Committee oversees drug use policies.',
                    'choices' => [
                        'A' => ['Infection Control Committee', false, 'Handles infection-related policies.'],
                        'B' => ['Pharmacy and Therapeutics Committee', true, null],
                        'C' => ['Ethics Committee', false, 'Handles ethical issues.'],
                        'D' => ['Quality Assurance Committee', false, 'Handles overall QA.'],
                    ],
                ],
                [
                    'text' => 'What is the primary role of a hospital pharmacist in a unit dose system?',
                    'rationale' => 'Unit dose systems require the pharmacist to prepare and dispense individual doses for each patient.',
                    'choices' => [
                        'A' => ['Bulk dispensing', false, 'Not the unit dose model.'],
                        'B' => ['Individual patient dose preparation', true, null],
                        'C' => ['Only IV preparation', false, 'Too narrow.'],
                        'D' => ['Only outpatient dispensing', false, 'Unit dose is inpatient.'],
                    ],
                ],
            ],

            'PK-02' => [
                [
                    'text' => 'Which pharmacokinetic parameter describes the volume of plasma cleared of drug per unit time?',
                    'rationale' => 'Clearance (CL) is the volume of plasma cleared of drug per unit time.',
                    'choices' => [
                        'A' => ['Volume of distribution', false, 'Apparent space in which drug is distributed.'],
                        'B' => ['Clearance', true, null],
                        'C' => ['Half-life', false, 'Time for concentration to halve.'],
                        'D' => ['Bioavailability', false, 'Fraction of dose reaching circulation.'],
                    ],
                ],
                [
                    'text' => 'What is the half-life of a drug if its elimination rate constant is 0.693 per hour?',
                    'rationale' => 'Half-life = 0.693 / k. So 0.693 / 0.693 = 1 hour.',
                    'choices' => [
                        'A' => ['0.5 hours', false, 'Incorrect calculation.'],
                        'B' => ['1 hour', true, null],
                        'C' => ['1.5 hours', false, 'Incorrect calculation.'],
                        'D' => ['2 hours', false, 'Incorrect calculation.'],
                    ],
                ],
            ],

            'PK-03' => [
                [
                    'text' => 'Which antidote is used for acetaminophen overdose?',
                    'rationale' => 'N-acetylcysteine replenishes glutathione, neutralizing the toxic metabolite NAPQI.',
                    'choices' => [
                        'A' => ['Naloxone', false, 'For opioid overdose.'],
                        'B' => ['N-acetylcysteine', true, null],
                        'C' => ['Atropine', false, 'For organophosphate poisoning.'],
                        'D' => ['Flumazenil', false, 'For benzodiazepine overdose.'],
                    ],
                ],
                [
                    'text' => 'Which heavy metal is chelated by dimercaprol?',
                    'rationale' => 'Dimercaprol chelates arsenic, gold, and mercury.',
                    'choices' => [
                        'A' => ['Iron', false, 'Chelated by deferoxamine.'],
                        'B' => ['Arsenic', true, null],
                        'C' => ['Lead', false, 'Chelated by EDTA or DMSA.'],
                        'D' => ['Copper', false, 'Chelated by penicillamine.'],
                    ],
                ],
            ],

            'PH-01' => [
                [
                    'text' => 'What is the primary purpose of Good Manufacturing Practice (GMP)?',
                    'rationale' => 'GMP ensures consistent production and control of pharmaceutical products according to quality standards.',
                    'choices' => [
                        'A' => ['Increase drug prices', false, 'Not the purpose.'],
                        'B' => ['Ensure product quality', true, null],
                        'C' => ['Reduce manufacturing costs', false, 'Not the primary purpose.'],
                        'D' => ['Speed up production', false, 'Not the primary purpose.'],
                    ],
                ],
                [
                    'text' => 'Which area of a pharmaceutical facility requires the highest level of cleanliness?',
                    'rationale' => 'Aseptic filling areas require ISO Class 5 or better conditions.',
                    'choices' => [
                        'A' => ['Warehouse', false, 'Storage area, not critical cleanliness.'],
                        'B' => ['Aseptic filling area', true, null],
                        'C' => ['Packaging area', false, 'Lower cleanliness requirement.'],
                        'D' => ['Quality control lab', false, 'Analysis area, not sterile manufacturing.'],
                    ],
                ],
            ],

            'PH-02' => [
                [
                    'text' => 'Which dosage form is prepared by dissolving a drug in an aqueous vehicle and sweetening it?',
                    'rationale' => 'A syrup is a concentrated aqueous solution of sugar or sugar substitute with the drug.',
                    'choices' => [
                        'A' => ['Elixir', false, 'Alcohol-based.'],
                        'B' => ['Syrup', true, null],
                        'C' => ['Emulsion', false, 'Two-phase liquid.'],
                        'D' => ['Suspension', false, 'Solid in liquid.'],
                    ],
                ],
            ],

            'PH-04' => [
                [
                    'text' => 'Which Philippine law governs the practice of pharmacy in the country?',
                    'rationale' => 'R.A. 10918 is the Philippine Pharmacy Act of 2016.',
                    'choices' => [
                        'A' => ['R.A. 7394', false, 'Consumer Act.'],
                        'B' => ['R.A. 10918', true, null],
                        'C' => ['R.A. 9165', false, 'Comprehensive Dangerous Drugs Act.'],
                        'D' => ['R.A. 9711', false, 'FDA Act of 2009.'],
                    ],
                ],
            ],

            'QC-02' => [
                [
                    'text' => 'Which technique is used to determine the concentration of a colored solution?',
                    'rationale' => 'Spectrophotometry measures absorbance of light, which correlates with concentration via Beer\'s Law.',
                    'choices' => [
                        'A' => ['Titration', false, 'Volumetric analysis, not color-based.'],
                        'B' => ['Spectrophotometry', true, null],
                        'C' => ['Gravimetry', false, 'Mass-based analysis.'],
                        'D' => ['Chromatography', false, 'Separation technique.'],
                    ],
                ],
                [
                    'text' => 'What does HPLC stand for?',
                    'rationale' => 'HPLC is High-Performance Liquid Chromatography.',
                    'choices' => [
                        'A' => ['High-Pressure Liquid Chemistry', false, 'Not the correct expansion.'],
                        'B' => ['High-Performance Liquid Chromatography', true, null],
                        'C' => ['High-Purity Liquid Chromatography', false, 'Not the correct expansion.'],
                        'D' => ['High-Performance Liquid Chemistry', false, 'Not the correct expansion.'],
                    ],
                ],
            ],
        ];

        $competencies = TosCompetency::query()
            ->whereIn('competency_code', array_keys($questions))
            ->get()
            ->keyBy('competency_code');

        $questionsProcessed = 0;
        $choicesProcessed = 0;

        DB::transaction(function () use (
            $questions,
            $competencies,
            &$questionsProcessed,
            &$choicesProcessed,
        ): void {
            foreach ($questions as $code => $items) {
                $competency = $competencies->get($code);

                if (! $competency) {
                    $this->command?->warn(
                        "Skipping {$code}: competency not found. Run TosSeeder first."
                    );

                    continue;
                }

                foreach ($items as $item) {
                    $question = Question::updateOrCreate(
                        [
                            'competency_id' => $competency->competency_id,
                            'question_text' => $item['text'],
                        ],
                        [
                            'hypercorrection_rationale' => $item['rationale'],
                            'is_diagnostic_pool' => false,
                            'research_form' => null,
                            'form_position' => null,
                            'cognitive_level' => $item['cognitive_level'] ?? null,
                            'is_active' => true,
                        ]
                    );

                    $questionsProcessed++;

                    foreach ($item['choices'] as $letter => $choice) {
                        [$text, $isCorrect, $fallacy] = $choice;

                        QuestionChoice::updateOrCreate(
                            [
                                'question_id' => $question->question_id,
                                'choice_letter' => $letter,
                            ],
                            [
                                'choice_text' => $text,
                                'is_correct' => $isCorrect,
                                'distractor_fallacy_note' => $fallacy,
                            ]
                        );

                        $choicesProcessed++;
                    }
                }
            }
        });

        $this->command?->info(
            "Question seeder: {$questionsProcessed} questions, {$choicesProcessed} choices."
        );
    }
}
