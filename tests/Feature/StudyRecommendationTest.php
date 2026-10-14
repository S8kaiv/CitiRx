<?php

use App\Models\MicroPearl;
use App\Models\Question;
use App\Models\TosCompetency;
use App\Models\User;
use App\Models\UserKnowledgeState;
use App\Services\ReadinessService;
use App\Services\StudyRecommendationService;
use Database\Seeders\TosSeeder;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->withoutVite();

    $this->seed(
        TosSeeder::class,
    );
});

function studyRecommendationStudent(
    bool $diagnosticCompleted = true,
): User {
    return User::factory()->create([
        'role' => 'student',

        'is_diagnostic_completed' => $diagnosticCompleted,

        'predicted_readiness_pct' => $diagnosticCompleted
                ? 60.00
                : null,
    ]);
}

/**
 * @param  array<string, float>  $masteryByCode
 */
function studyRecommendationStates(
    User $student,
    array $masteryByCode = [],
): void {
    $competencies = TosCompetency::query()
        ->orderBy('domain_id')
        ->orderBy('order_index')
        ->get();

    foreach ($competencies as $competency) {
        $mastery = (float) (
            $masteryByCode[
                $competency->competency_code
            ] ?? 0.75
        );

        UserKnowledgeState::query()->create([
            'user_id' => $student->user_id,

            'competency_id' => $competency->competency_id,

            'current_mastery_p_l' => $mastery,

            'total_attempts' => 10,

            'total_correct' => (int) round(
                $mastery * 10,
            ),

            'last_evaluated_at' => now(),
        ]);
    }
}

function studyRecommendationQuestion(
    TosCompetency $competency,
    ?string $researchForm = null,
    bool $active = true,
): Question {
    return Question::query()->create([
        'competency_id' => $competency->competency_id,

        'question_text' => 'Development question for recommendation testing?',

        'hypercorrection_rationale' => 'Development rationale.',

        'is_diagnostic_pool' => false,

        'is_active' => $active,

        'research_form' => $researchForm,
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 */
function studyRecommendationPearl(
    TosCompetency $competency,
    array $overrides = [],
): MicroPearl {
    $reviewer = User::factory()->create([
        'role' => 'faculty',
    ]);

    $nextOrder = (
        (int) MicroPearl::query()
            ->where(
                'competency_id',
                $competency->competency_id,
            )
            ->max('order_index')
    ) + 1;

    $title = $overrides['title']
        ?? 'Recommendation Pearl '.$nextOrder;

    $pearl = new MicroPearl;

    $pearl->forceFill(
        array_replace(
            [
                'competency_id' => $competency->competency_id,

                'slug' => Str::slug($title)
                    .'-'
                    .Str::lower(
                        Str::random(8),
                    ),

                'title' => $title,

                'mnemonic_summary' => 'A reviewed recommendation summary.',

                'lesson_content' => 'Detailed reviewed learning content.',

                'learning_objectives' => [
                    'Review the selected competency.',
                ],

                'key_points' => [
                    'Focus on the central concept.',
                ],

                'common_pitfalls' => [
                    'Avoid confusing related concepts.',
                ],

                'recall_prompts' => [
                    [
                        'question' => 'What is the central concept?',

                        'answer' => 'The concept described in the pearl.',
                    ],
                ],

                'source_references' => [
                    'Development reference.',
                ],

                'estimated_read_seconds' => 60,

                'order_index' => $nextOrder,

                'content_status' => MicroPearl::STATUS_PUBLISHED,

                'rights_status' => MicroPearl::RIGHTS_ORIGINAL,

                'is_ai_generated' => false,

                'ai_enrichment_allowed' => false,

                'generation_metadata' => null,

                'content_version' => 1,

                'reviewed_by' => $reviewer->user_id,

                'reviewed_at' => now(),

                'published_at' => now(),
            ],
            $overrides,
        ),
    );

    $pearl->save();

    return $pearl;
}

function studyRecommendationWeakChemistryStates(
    User $student,
): void {
    studyRecommendationStates(
        $student,
        [
            'PC-01' => 0.10,
            'PC-02' => 0.20,
            'PC-03' => 0.30,
        ],
    );
}

it(
    'requires the diagnostic before generating a recommendation',
    function (): void {
        $student = studyRecommendationStudent(
            diagnosticCompleted: false,
        );

        $result = app(
            StudyRecommendationService::class,
        )->forUser($student);

        expect($result['has_data'])
            ->toBeFalse()
            ->and($result['status'])
            ->toBe('diagnostic_required')
            ->and($result['requires_diagnostic'])
            ->toBeTrue()
            ->and($result['priority_domain'])
            ->toBeNull();
    },
);

it(
    'returns a safe unavailable state when knowledge states are missing',
    function (): void {
        $student =
            studyRecommendationStudent();

        $result = app(
            StudyRecommendationService::class,
        )->forUser($student);

        expect($result['has_data'])
            ->toBeFalse()
            ->and($result['status'])
            ->toBe('unavailable')
            ->and($result['recommended_action'])
            ->toBeNull();
    },
);

it(
    'selects the lowest weighted domain and its weakest competencies',
    function (): void {
        $student =
            studyRecommendationStudent();

        studyRecommendationWeakChemistryStates(
            $student,
        );

        $result = app(
            StudyRecommendationService::class,
        )->forUser($student);

        expect($result['has_data'])
            ->toBeTrue()
            ->and(
                $result['priority_domain']['domain_name'],
            )
            ->toBe('Pharmaceutical Chemistry')
            ->and(
                $result['priority_domain']['mastery_pct'],
            )
            ->toBe(16.5)
            ->and(
                array_column(
                    $result['priority_competencies'],
                    'code',
                ),
            )
            ->toBe([
                'PC-01',
                'PC-02',
                'PC-03',
            ]);
    },
);

it(
    'uses maintenance mode when every domain meets the mastery threshold',
    function (): void {
        $student =
            studyRecommendationStudent();

        $masteries = TosCompetency::query()
            ->pluck('competency_code')
            ->mapWithKeys(
                fn (string $code): array => [
                    $code => 0.90,
                ],
            )
            ->all();

        studyRecommendationStates(
            $student,
            $masteries,
        );

        $result = app(
            StudyRecommendationService::class,
        )->forUser($student);

        expect($result['mode'])
            ->toBe('maintenance')
            ->and($result['reason'])
            ->toContain('maintenance');
    },
);

it(
    'recommends practice only when the priority domain has eligible questions',
    function (): void {
        $student =
            studyRecommendationStudent();

        studyRecommendationWeakChemistryStates(
            $student,
        );

        $competency = TosCompetency::query()
            ->where(
                'competency_code',
                'PC-01',
            )
            ->firstOrFail();

        studyRecommendationQuestion(
            $competency,
        );

        $result = app(
            StudyRecommendationService::class,
        )->forUser($student);

        expect($result['recommended_action'])
            ->not->toBeNull()
            ->and(
                $result['recommended_action']['type'],
            )
            ->toBe('practice')
            ->and(
                $result['recommended_action']['domain_id'],
            )
            ->toBe($competency->domain_id)
            ->and(
                $result['recommended_action']['suggested_length'],
            )
            ->toBe(10);
    },
);

it(
    'never treats research form questions as practice eligible',
    function (): void {
        $student =
            studyRecommendationStudent();

        studyRecommendationWeakChemistryStates(
            $student,
        );

        $competency = TosCompetency::query()
            ->where(
                'competency_code',
                'PC-01',
            )
            ->firstOrFail();

        studyRecommendationQuestion(
            $competency,
            Question::FORM_POST_TEST_B,
        );

        $result = app(
            StudyRecommendationService::class,
        )->forUser($student);

        expect($result['recommended_action'])
            ->toBeNull();
    },
);

it(
    'recommends only pearls that pass the publication gate',
    function (): void {
        $student =
            studyRecommendationStudent();

        studyRecommendationWeakChemistryStates(
            $student,
        );

        $competency = TosCompetency::query()
            ->where(
                'competency_code',
                'PC-01',
            )
            ->firstOrFail();

        $published = studyRecommendationPearl(
            $competency,
            [
                'title' => 'Published Recommendation Pearl',
            ],
        );

        studyRecommendationPearl(
            $competency,
            [
                'title' => 'Hidden Draft Pearl',

                'content_status' => MicroPearl::STATUS_DRAFT,
            ],
        );

        studyRecommendationPearl(
            $competency,
            [
                'title' => 'Missing Sources Pearl',

                'source_references' => [],
            ],
        );

        $result = app(
            StudyRecommendationService::class,
        )->forUser($student);

        expect(
            array_column(
                $result['recommended_pearls'],
                'slug',
            ),
        )->toBe([
            $published->slug,
        ]);
    },
);

it(
    'returns identical decisions for identical inputs without exposing pii',
    function (): void {
        $student =
            studyRecommendationStudent();

        studyRecommendationWeakChemistryStates(
            $student,
        );

        $service = app(
            StudyRecommendationService::class,
        );

        $first =
            $service->forUser($student);

        $second =
            $service->forUser($student);

        $encoded = json_encode(
            $first,
            JSON_THROW_ON_ERROR,
        );

        expect($first)
            ->toBe($second)
            ->and($encoded)
            ->not->toContain($student->user_id)
            ->not->toContain($student->email)
            ->not->toContain($student->first_name)
            ->not->toContain($student->last_name);
    },
);

it(
    'can reuse a previously calculated readiness breakdown',
    function (): void {
        $student =
            studyRecommendationStudent();

        studyRecommendationWeakChemistryStates(
            $student,
        );

        $breakdown = app(
            ReadinessService::class,
        )->breakdown($student);

        $service = app(
            StudyRecommendationService::class,
        );

        expect(
            $service->forUser(
                $student,
                $breakdown,
            ),
        )->toBe(
            $service->forUser($student),
        );
    },
);

it(
    'shows the personalized study plan on the student dashboard',
    function (): void {
        $student =
            studyRecommendationStudent();

        studyRecommendationWeakChemistryStates(
            $student,
        );

        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText(
                'Personalized Study Plan',
            )
            ->assertSeeText(
                'Pharmaceutical Chemistry',
            )
            ->assertSeeText(
                StudyRecommendationService::ENGINE_VERSION,
            );
    },
);
