<?php

use App\Models\MicroPearl;
use App\Models\MicroPearlEdit;
use App\Models\MicroPearlImport;
use App\Models\User;
use App\Services\MicroPearlImportService;
use Database\Seeders\TosSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->withoutVite();

    Storage::fake('local');

    $this->seed(TosSeeder::class);
});

/**
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function pearlImportRow(
    array $overrides = [],
): array {
    return array_replace([
        'competency_code' => 'PC-01',
        'title' => 'Imported Calcium Lesson',
        'mnemonic_summary' => 'Calcium supports bones and coagulation.',
        'lesson_content' => 'Calcium is essential for bone structure, muscle contraction, nerve transmission, and coagulation.',
        'learning_objectives' => 'Explain calcium functions || Identify important clinical applications',
        'key_points' => 'Most calcium is stored in bone || Ionized calcium is biologically active',
        'common_pitfalls' => 'Do not confuse total calcium with ionized calcium',
        'recall_prompts' => 'Where is most calcium stored? | Bone || Which form is biologically active? | Ionized calcium',
        'source_references' => 'Faculty-authored lesson || Katzung Basic and Clinical Pharmacology, 15th edition',
        'estimated_read_seconds' => '180',
        'order_index' => '1',
        'rights_status' => 'original',
        'is_ai_generated' => '0',
        'ai_enrichment_allowed' => '0',
        'generation_provider' => '',
        'generation_model' => '',
        'prompt_version' => '',
    ], $overrides);
}

/**
 * @param  array<int, array<string, string>>  $rows
 * @param  null|array<int, string>  $headers
 */
function pearlImportUpload(
    array $rows,
    ?array $headers = null,
): UploadedFile {
    $headers ??=
        MicroPearlImportService::HEADERS;

    $stream = fopen(
        'php://temp',
        'w+b',
    );

    if ($stream === false) {
        throw new RuntimeException(
            'Unable to create the test CSV.',
        );
    }

    fputcsv(
        $stream,
        $headers,
        ',',
        '"',
        '',
    );

    foreach ($rows as $row) {
        fputcsv(
            $stream,
            array_map(
                fn (string $header): string => $row[$header] ?? '',
                $headers,
            ),
            ',',
            '"',
            '',
        );
    }

    rewind($stream);

    $contents = stream_get_contents(
        $stream,
    );

    fclose($stream);

    if ($contents === false) {
        throw new RuntimeException(
            'Unable to read the test CSV.',
        );
    }

    return UploadedFile::fake()
        ->createWithContent(
            'micro-pearls.csv',
            $contents,
        );
}

it('redirects guests from the import page', function (): void {
    $this->get(
        route('content.pearls.imports.create'),
    )->assertRedirect(route('login'));
});

it('blocks students from the import page', function (): void {
    $student = User::factory()->create([
        'role' => 'student',
    ]);

    $this->actingAs($student)
        ->get(
            route('content.pearls.imports.create'),
        )
        ->assertForbidden();
});

it('allows authorized editors to open the import page', function (
    string $role,
): void {
    $editor = User::factory()->create([
        'role' => $role,
    ]);

    $this->actingAs($editor)
        ->get(
            route('content.pearls.imports.create'),
        )
        ->assertOk()
        ->assertSeeText(
            'Import Micro Pearl lessons',
        )
        ->assertSeeText(
            'Download CSV Template',
        );
})->with([
    'administrator' => 'admin',
    'faculty' => 'faculty',
]);

it('creates a preview without creating lessons', function (): void {
    $faculty = User::factory()->create([
        'role' => 'faculty',
    ]);

    $response = $this->actingAs($faculty)
        ->post(
            route(
                'content.pearls.imports.preview',
            ),
            [
                'csv' => pearlImportUpload([
                    pearlImportRow(),
                ]),
            ],
        );

    $import = MicroPearlImport::query()
        ->firstOrFail();

    $response
        ->assertRedirect(
            route(
                'content.pearls.imports.show',
                ['import' => $import],
            ),
        )
        ->assertSessionHasNoErrors();

    expect(MicroPearl::query()->count())
        ->toBe(0)
        ->and($import->row_count)
        ->toBe(1)
        ->and($import->status)
        ->toBe(
            MicroPearlImport::STATUS_PREVIEWED,
        )
        ->and(
            Storage::disk('local')->exists(
                $import->storage_path,
            ),
        )
        ->toBeTrue();
});

it('rejects an invalid CSV header', function (): void {
    $faculty = User::factory()->create([
        'role' => 'faculty',
    ]);

    $this->actingAs($faculty)
        ->from(
            route(
                'content.pearls.imports.create',
            ),
        )
        ->post(
            route(
                'content.pearls.imports.preview',
            ),
            [
                'csv' => pearlImportUpload(
                    [
                        [
                            'bad_header' => 'bad value',
                        ],
                    ],
                    ['bad_header'],
                ),
            ],
        )
        ->assertRedirect(
            route(
                'content.pearls.imports.create',
            ),
        )
        ->assertSessionHasErrors('csv');

    expect(
        MicroPearlImport::query()->count(),
    )->toBe(0);
});

it('rejects an unknown competency code', function (): void {
    $faculty = User::factory()->create([
        'role' => 'faculty',
    ]);

    $this->actingAs($faculty)
        ->post(
            route(
                'content.pearls.imports.preview',
            ),
            [
                'csv' => pearlImportUpload([
                    pearlImportRow([
                        'competency_code' => 'UNKNOWN-01',
                    ]),
                ]),
            ],
        )
        ->assertSessionHasErrors('csv');

    expect(
        MicroPearlImport::query()->count(),
    )->toBe(0);
});

it('rejects duplicate CSV lessons', function (): void {
    $faculty = User::factory()->create([
        'role' => 'faculty',
    ]);

    $row = pearlImportRow();

    $this->actingAs($faculty)
        ->post(
            route(
                'content.pearls.imports.preview',
            ),
            [
                'csv' => pearlImportUpload([
                    $row,
                    $row,
                ]),
            ],
        )
        ->assertSessionHasErrors('csv');

    expect(
        MicroPearlImport::query()->count(),
    )->toBe(0);
});

it('rejects AI lessons without generation metadata', function (): void {
    $faculty = User::factory()->create([
        'role' => 'faculty',
    ]);

    $this->actingAs($faculty)
        ->post(
            route(
                'content.pearls.imports.preview',
            ),
            [
                'csv' => pearlImportUpload([
                    pearlImportRow([
                        'is_ai_generated' => '1',
                        'generation_provider' => '',
                        'generation_model' => '',
                        'prompt_version' => '',
                    ]),
                ]),
            ],
        )
        ->assertSessionHasErrors('csv');

    expect(
        MicroPearlImport::query()->count(),
    )->toBe(0);
});

it('prevents another editor from viewing a preview', function (): void {
    $owner = User::factory()->create([
        'role' => 'faculty',
    ]);

    $otherFaculty = User::factory()->create([
        'role' => 'faculty',
    ]);

    $this->actingAs($owner)
        ->post(
            route(
                'content.pearls.imports.preview',
            ),
            [
                'csv' => pearlImportUpload([
                    pearlImportRow(),
                ]),
            ],
        )
        ->assertSessionHasNoErrors();

    $import = MicroPearlImport::query()
        ->firstOrFail();

    $this->actingAs($otherFaculty)
        ->get(
            route(
                'content.pearls.imports.show',
                ['import' => $import],
            ),
        )
        ->assertForbidden();
});

it('requires the current password to commit', function (): void {
    $faculty = User::factory()->create([
        'role' => 'faculty',
    ]);

    $this->actingAs($faculty)
        ->post(
            route(
                'content.pearls.imports.preview',
            ),
            [
                'csv' => pearlImportUpload([
                    pearlImportRow(),
                ]),
            ],
        );

    $import = MicroPearlImport::query()
        ->firstOrFail();

    $this->post(
        route(
            'content.pearls.imports.commit',
            ['import' => $import],
        ),
        [
            'expected_lock_version' => $import->lock_version,

            'reason' => 'Import approved faculty lessons.',

            'current_password' => 'incorrect-password',
        ],
    )->assertSessionHasErrors(
        'current_password',
    );

    expect(
        MicroPearl::query()->count(),
    )->toBe(0);
});

it('rejects an expired preview', function (): void {
    $faculty = User::factory()->create([
        'role' => 'faculty',
    ]);

    $this->actingAs($faculty)
        ->post(
            route(
                'content.pearls.imports.preview',
            ),
            [
                'csv' => pearlImportUpload([
                    pearlImportRow(),
                ]),
            ],
        );

    $import = MicroPearlImport::query()
        ->firstOrFail();

    $import->forceFill([
        'expires_at' => now()->subMinute(),
    ])->save();

    $this->post(
        route(
            'content.pearls.imports.commit',
            ['import' => $import],
        ),
        [
            'expected_lock_version' => $import->lock_version,

            'reason' => 'Import approved faculty lessons.',

            'current_password' => 'password',
        ],
    )->assertSessionHasErrors('csv');

    expect(
        MicroPearl::query()->count(),
    )->toBe(0);
});

it('rejects a changed file after preview', function (): void {
    $faculty = User::factory()->create([
        'role' => 'faculty',
    ]);

    $this->actingAs($faculty)
        ->post(
            route(
                'content.pearls.imports.preview',
            ),
            [
                'csv' => pearlImportUpload([
                    pearlImportRow(),
                ]),
            ],
        );

    $import = MicroPearlImport::query()
        ->firstOrFail();

    Storage::disk('local')->put(
        $import->storage_path,
        'tampered content',
    );

    $this->post(
        route(
            'content.pearls.imports.commit',
            ['import' => $import],
        ),
        [
            'expected_lock_version' => $import->lock_version,

            'reason' => 'Import approved faculty lessons.',

            'current_password' => 'password',
        ],
    )->assertSessionHasErrors('csv');

    expect(
        MicroPearl::query()->count(),
    )->toBe(0);
});

it('commits all lessons as private drafts', function (): void {
    $faculty = User::factory()->create([
        'role' => 'faculty',
    ]);

    $student = User::factory()->create([
        'role' => 'student',
    ]);

    $this->actingAs($faculty)
        ->post(
            route(
                'content.pearls.imports.preview',
            ),
            [
                'csv' => pearlImportUpload([
                    pearlImportRow(),

                    pearlImportRow([
                        'title' => 'Imported Ionic Bond Lesson',

                        'order_index' => '2',

                        'lesson_content' => 'An ionic bond forms through electrostatic attraction between oppositely charged ions.',
                    ]),
                ]),
            ],
        )
        ->assertSessionHasNoErrors();

    $import = MicroPearlImport::query()
        ->firstOrFail();

    $storedPath = $import->storage_path;

    $this->post(
        route(
            'content.pearls.imports.commit',
            ['import' => $import],
        ),
        [
            'expected_lock_version' => $import->lock_version,

            'reason' => 'Import faculty-authored lessons for independent review.',

            'current_password' => 'password',
        ],
    )
        ->assertRedirect(
            route('content.pearls.index'),
        )
        ->assertSessionHasNoErrors();

    $pearls = MicroPearl::query()
        ->orderBy('order_index')
        ->get();

    expect($pearls)
        ->toHaveCount(2)
        ->and(
            $pearls->every(
                fn (MicroPearl $pearl): bool => $pearl->content_status
                        === MicroPearl::STATUS_DRAFT,
            ),
        )
        ->toBeTrue()
        ->and(
            $pearls->every(
                fn (MicroPearl $pearl): bool => $pearl->created_by
                        === $faculty->user_id,
            ),
        )
        ->toBeTrue()
        ->and(
            MicroPearlEdit::query()
                ->where('action', 'created')
                ->count(),
        )
        ->toBe(2);

    $import->refresh();

    expect($import->status)
        ->toBe(
            MicroPearlImport::STATUS_COMMITTED,
        )
        ->and($import->storage_path)
        ->toBeNull()
        ->and(
            Storage::disk('local')->exists(
                $storedPath,
            ),
        )
        ->toBeFalse();

    $this->actingAs($student)
        ->get(route('pearls.index'))
        ->assertOk()
        ->assertDontSeeText(
            'Imported Calcium Lesson',
        )
        ->assertDontSeeText(
            'Imported Ionic Bond Lesson',
        );
});

it('rejects replaying a committed import', function (): void {
    $faculty = User::factory()->create([
        'role' => 'faculty',
    ]);

    $this->actingAs($faculty)
        ->post(
            route(
                'content.pearls.imports.preview',
            ),
            [
                'csv' => pearlImportUpload([
                    pearlImportRow(),
                ]),
            ],
        );

    $import = MicroPearlImport::query()
        ->firstOrFail();

    $payload = [
        'expected_lock_version' => $import->lock_version,

        'reason' => 'Import faculty-authored lessons.',

        'current_password' => 'password',
    ];

    $this->post(
        route(
            'content.pearls.imports.commit',
            ['import' => $import],
        ),
        $payload,
    )->assertSessionHasNoErrors();

    $this->post(
        route(
            'content.pearls.imports.commit',
            ['import' => $import],
        ),
        $payload,
    )->assertSessionHasErrors('csv');

    expect(
        MicroPearl::query()->count(),
    )->toBe(1);
});
