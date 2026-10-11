<?php

use App\Models\MicroPearl;
use App\Models\TosCompetency;
use App\Models\User;
use Database\Seeders\TosSeeder;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(TosSeeder::class);
});

function microPearlTestCreate(
    array $overrides = []
): MicroPearl {
    $competencyCode = $overrides['competency_code'] ?? 'PC-01';

    unset(
        $overrides['competency_code']
    );

    $competency = TosCompetency::query()
        ->where(
            'competency_code',
            $competencyCode
        )
        ->firstOrFail();

    $reviewer = User::factory()->create([
        'role' => 'faculty',
    ]);

    $nextOrder = (
        (int) MicroPearl::query()
            ->where(
                'competency_id',
                $competency->competency_id
            )
            ->max('order_index')
    ) + 1;

    $title = $overrides['title']
        ?? 'Development Pearl '.$nextOrder;

    $pearl = new MicroPearl;

    $pearl->forceFill(
        array_replace(
            [
                'competency_id' => $competency->competency_id,

                'slug' => Str::slug($title)
                    .'-'
                    .Str::lower(
                        Str::random(8)
                    ),

                'title' => $title,

                'mnemonic_summary' => 'A concise reviewed summary.',

                'lesson_content' => 'This is detailed lesson content used only by the automated test.',

                'learning_objectives' => [
                    'Explain the tested concept.',
                ],

                'key_points' => [
                    'Remember the central concept.',
                ],

                'common_pitfalls' => [
                    'Do not confuse related terms.',
                ],

                'recall_prompts' => [
                    [
                        'question' => 'What is the central concept?',

                        'answer' => 'The concept described in the lesson.',
                    ],
                ],

                'source_references' => [
                    'Test reference used only by the automated suite.',
                ],

                'estimated_read_seconds' => 45,
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
            $overrides
        )
    );

    $pearl->save();

    return $pearl;
}

it(
    'redirects guests from the pearl library',
    function (): void {
        $this->get(
            route('pearls.index')
        )->assertRedirect(
            route('login')
        );
    }
);

it(
    'allows authenticated roles to view published pearls',
    function (
        string $role
    ): void {
        $pearl = microPearlTestCreate([
            'title' => 'Published Test Pearl',
        ]);

        $user = User::factory()->create([
            'role' => $role,
        ]);

        $this->actingAs($user)
            ->get(route('pearls.index'))
            ->assertOk()
            ->assertSeeText(
                $pearl->title
            );
    }
)->with([
    'student',
    'faculty',
    'admin',
]);

it(
    'shows the detailed pearl lesson',
    function (): void {
        $pearl = microPearlTestCreate([
            'title' => 'Detailed Test Pearl',

            'lesson_content' => 'Detailed lesson verification text.',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
        ]);

        $this->actingAs($student)
            ->get(
                route(
                    'pearls.show',
                    $pearl
                )
            )
            ->assertOk()
            ->assertSeeText(
                'Detailed lesson verification text.'
            )
            ->assertSeeText(
                'Active Recall'
            );
    }
);

it(
    'does not expose unpublished or incomplete pearls',
    function (): void {
        $draft = microPearlTestCreate([
            'title' => 'Hidden Draft',
            'content_status' => MicroPearl::STATUS_DRAFT,
        ]);

        $pendingRights = microPearlTestCreate([
            'title' => 'Pending Rights',
            'rights_status' => MicroPearl::RIGHTS_PENDING,
        ]);

        $future = microPearlTestCreate([
            'title' => 'Future Publication',
            'published_at' => now()->addDay(),
        ]);

        $missingSources = microPearlTestCreate([
            'title' => 'Missing Sources',
            'source_references' => [],
        ]);

        $untrackedAiContent = microPearlTestCreate([
            'title' => 'Untracked AI Content',
            'is_ai_generated' => true,
            'generation_metadata' => null,
        ]);

        $student = User::factory()->create([
            'role' => 'student',
        ]);

        $this->actingAs($student)
            ->get(route('pearls.index'))
            ->assertOk()
            ->assertDontSeeText(
                $draft->title
            )
            ->assertDontSeeText(
                $pendingRights->title
            )
            ->assertDontSeeText(
                $future->title
            )
            ->assertDontSeeText(
                $missingSources->title
            )
            ->assertDontSeeText(
                $untrackedAiContent->title
            );

        foreach (
            [
                $draft,
                $pendingRights,
                $future,
                $missingSources,
                $untrackedAiContent,
            ] as $hiddenPearl
        ) {
            $this->get(
                route(
                    'pearls.show',
                    $hiddenPearl
                )
            )->assertNotFound();
        }
    }
);

it(
    'filters pearls by domain and competency',
    function (): void {
        $chemistry = microPearlTestCreate([
            'competency_code' => 'PC-01',
            'title' => 'Chemistry Pearl',
        ])->load('competency');

        $biochemistry = microPearlTestCreate([
            'competency_code' => 'PB-01',
            'title' => 'Biochemistry Pearl',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
        ]);

        $this->actingAs($student)
            ->get(
                route(
                    'pearls.index',
                    [
                        'domain_id' => $chemistry
                            ->competency
                            ->domain_id,
                    ]
                )
            )
            ->assertOk()
            ->assertSeeText(
                $chemistry->title
            )
            ->assertDontSeeText(
                $biochemistry->title
            )
            ->assertSeeText('PC-01')
            ->assertSeeText('PC-02')
            ->assertSeeText('PC-03')
            ->assertDontSeeText('PB-01')
            ->assertDontSeeText('PP-01');

        $this->get(
            route(
                'pearls.index',
                [
                    'competency_id' => $chemistry
                        ->competency_id,
                ]
            )
        )
            ->assertOk()
            ->assertSeeText(
                $chemistry->title
            )
            ->assertDontSeeText(
                $biochemistry->title
            );
    }
);

it(
    'rejects a competency outside the selected domain',
    function (): void {
        $chemistry = TosCompetency::query()
            ->where(
                'competency_code',
                'PC-01'
            )
            ->firstOrFail();

        $biochemistry = TosCompetency::query()
            ->where(
                'competency_code',
                'PB-01'
            )
            ->firstOrFail();

        $student = User::factory()->create([
            'role' => 'student',
        ]);

        $this->actingAs($student)
            ->from(route('pearls.index'))
            ->get(
                route(
                    'pearls.index',
                    [
                        'domain_id' => $chemistry->domain_id,

                        'competency_id' => $biochemistry
                            ->competency_id,
                    ]
                )
            )
            ->assertRedirect(
                route('pearls.index')
            )
            ->assertSessionHasErrors(
                'competency_id'
            );
    }
);

it(
    'searches title summary and lesson content',
    function (): void {
        $matching = microPearlTestCreate([
            'title' => 'Absorption Fundamentals',

            'lesson_content' => 'Distribution is explained in this lesson.',
        ]);

        $other = microPearlTestCreate([
            'competency_code' => 'PB-01',
            'title' => 'Unrelated Topic',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
        ]);

        $this->actingAs($student)
            ->get(
                route(
                    'pearls.index',
                    [
                        'q' => 'distribution',
                    ]
                )
            )
            ->assertOk()
            ->assertSeeText(
                $matching->title
            )
            ->assertDontSeeText(
                $other->title
            );
    }
);

it(
    'escapes stored lesson content',
    function (): void {
        microPearlTestCreate([
            'title' => '<script>alert("x")</script>',

            'lesson_content' => '<script>alert("lesson")</script>',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
        ]);

        $this->actingAs($student)
            ->get(route('pearls.index'))
            ->assertOk()
            ->assertDontSee(
                '<script>',
                false
            )
            ->assertSee(
                '&lt;script&gt;',
                false
            );
    }
);

it(
    'shows the empty state when nothing matches',
    function (): void {
        $student = User::factory()->create([
            'role' => 'student',
        ]);

        $this->actingAs($student)
            ->get(
                route(
                    'pearls.index',
                    [
                        'q' => 'no-such-pearl-topic',
                    ]
                )
            )
            ->assertOk()
            ->assertSeeText(
                'No published pearls found'
            );
    }
);

it(
    'shows the official taxonomy when no published pearl matches',
    function (): void {
        $student = User::factory()->create([
            'role' => 'student',
        ]);

        $this->actingAs($student)
            ->get(
                route(
                    'pearls.index',
                    [
                        'q' => 'no-such-pearl-topic',
                    ]
                )
            )
            ->assertOk()
            ->assertSeeText(
                'No published pearls found'
            )
            ->assertSeeText(
                'Pharmaceutical Chemistry'
            )
            ->assertSeeText(
                'PC-01'
            )
            ->assertSeeText(
                'QC-04'
            );
    }
);
