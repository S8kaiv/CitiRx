<?php

use App\Models\AdminAuditLog;
use App\Models\AssessmentSession;
use App\Models\Cohort;
use App\Models\CohortAssignmentImport;
use App\Models\User;
use App\Services\CohortAssignmentImportService;
use Illuminate\Http\UploadedFile;

function cohortImportAdministrator(
    array $overrides = [],
): User {
    return User::factory()->create(array_merge([
        'role' => 'admin',
        'cohort_id' => null,
        'student_id' => null,
    ], $overrides));
}

function cohortImportCohort(
    string $name = 'BS Pharmacy Batch 2026',
    string $academicYear = '2025-2026',
    bool $archived = false,
): Cohort {
    $cohort = Cohort::query()->create([
        'cohort_name' => $name,
        'academic_year' => $academicYear,
        'target_phle_date' => now()->addYear()->toDateString(),
    ]);

    if ($archived) {
        $cohort->forceFill([
            'archived_at' => now(),
        ])->save();
    }

    return $cohort->fresh();
}

function cohortImportUser(
    string $role = 'student',
    ?Cohort $cohort = null,
    array $overrides = [],
): User {
    static $studentSequence = 0;

    $studentSequence++;

    return User::factory()->create(array_merge([
        'role' => $role,
        'cohort_id' => $cohort?->cohort_id,
        'student_id' => $role === 'student'
            ? sprintf('IMPORT-%05d', $studentSequence)
            : null,
    ], $overrides));
}

/**
 * @return array<string, string>
 */
function cohortImportCsvRow(
    User $user,
    Cohort $cohort,
    array $overrides = [],
): array {
    return array_merge([
        'email' => $user->email,
        'student_id' => (string) ($user->student_id ?? ''),
        'role' => $user->role,
        'cohort_name' => $cohort->cohort_name,
        'academic_year' => $cohort->academic_year,
    ], $overrides);
}

/**
 * @param  array<int, array<string, string>>  $rows
 */
function cohortImportCsv(
    array $rows,
    string $filename = 'cohort-assignments.csv',
): UploadedFile {
    $header = [
        'email',
        'student_id',
        'role',
        'cohort_name',
        'academic_year',
    ];

    $stream = fopen('php://temp', 'w+b');

    if ($stream === false) {
        throw new RuntimeException(
            'Unable to create the test CSV stream.',
        );
    }

    fputcsv(
        $stream,
        $header,
        ',',
        '"',
        '',
    );

    foreach ($rows as $row) {
        fputcsv(
            $stream,
            array_map(
                fn (string $column): string => (string) (
                    $row[$column] ?? ''
                ),
                $header,
            ),
            ',',
            '"',
            '',
        );
    }

    rewind($stream);

    $contents = stream_get_contents($stream);

    fclose($stream);

    if ($contents === false) {
        throw new RuntimeException(
            'Unable to read the test CSV stream.',
        );
    }

    return UploadedFile::fake()->createWithContent(
        $filename,
        $contents,
    );
}

/**
 * @param  array<int, array<string, string>>  $rows
 */
function cohortImportPreview(
    User $administrator,
    array $rows,
): CohortAssignmentImport {
    return app(
        CohortAssignmentImportService::class,
    )->createPreview(
        $administrator,
        cohortImportCsv($rows),
    );
}

function cohortImportCommitPayload(
    CohortAssignmentImport $import,
    array $overrides = [],
): array {
    return array_merge([
        'lock_version' => $import->lock_version,
        'reason' => 'Approved cohort assignment import.',
        'current_password' => 'password',
    ], $overrides);
}

test('guests are redirected from cohort assignment imports', function (): void {
    $this->get(
        route('admin.cohort-imports.create'),
    )->assertRedirect(
        route('login'),
    );
});

test('non administrators cannot access cohort assignment imports', function (
    string $role,
): void {
    $user = cohortImportUser($role);

    $this->actingAs($user)
        ->get(
            route('admin.cohort-imports.create'),
        )
        ->assertForbidden();
})->with([
    'student',
    'faculty',
]);

test('a valid csv creates a preview without changing users', function (): void {
    $administrator = cohortImportAdministrator();
    $oldCohort = cohortImportCohort(
        'Old Cohort',
        '2024-2025',
    );
    $targetCohort = cohortImportCohort();
    $student = cohortImportUser(
        'student',
        $oldCohort,
    );

    $response = $this->actingAs($administrator)
        ->post(
            route('admin.cohort-imports.preview'),
            [
                'csv' => cohortImportCsv([
                    cohortImportCsvRow(
                        $student,
                        $targetCohort,
                    ),
                ]),
            ],
        );

    $import = CohortAssignmentImport::query()
        ->firstOrFail();

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(
            route(
                'admin.cohort-imports.show',
                ['import' => $import],
            ),
        );

    expect($student->fresh()->cohort_id)
        ->toBe($oldCohort->cohort_id)
        ->and($import->committed_at)
        ->toBeNull()
        ->and($import->summary)
        ->toBe([
            'total' => 1,
            'changed' => 1,
            'unchanged' => 0,
        ]);
});

test('a preview stores internal ids without raw personal identifiers', function (): void {
    $administrator = cohortImportAdministrator();
    $targetCohort = cohortImportCohort();
    $student = cohortImportUser(
        'student',
        null,
        [
            'first_name' => 'ConfidentialFirst',
            'last_name' => 'ConfidentialLast',
            'email' => 'sensitive-person@citirx.test',
            'student_id' => 'PRIVATE-2026-0001',
        ],
    );

    $import = cohortImportPreview(
        $administrator,
        [
            cohortImportCsvRow(
                $student,
                $targetCohort,
            ),
        ],
    );

    $storedPreview = json_encode(
        [
            'assignment_rows' => $import->assignment_rows,
            'summary' => $import->summary,
        ],
        JSON_THROW_ON_ERROR,
    );

    expect($storedPreview)
        ->toContain($student->user_id)
        ->toContain($targetCohort->cohort_id)
        ->not->toContain($student->email)
        ->not->toContain((string) $student->student_id)
        ->not->toContain($student->first_name)
        ->not->toContain($student->last_name);
});

test('an unknown user is rejected', function (): void {
    $administrator = cohortImportAdministrator();
    $targetCohort = cohortImportCohort();

    $this->from(
        route('admin.cohort-imports.create'),
    )->actingAs($administrator)
        ->post(
            route('admin.cohort-imports.preview'),
            [
                'csv' => cohortImportCsv([
                    [
                        'email' => 'missing-user@citirx.test',
                        'student_id' => 'MISSING-001',
                        'role' => 'student',
                        'cohort_name' => $targetCohort->cohort_name,
                        'academic_year' => $targetCohort->academic_year,
                    ],
                ]),
            ],
        )
        ->assertRedirect(
            route('admin.cohort-imports.create'),
        )
        ->assertSessionHasErrors([
            'csv' => 'CSV line 2 does not match an existing user.',
        ]);

    expect(CohortAssignmentImport::query()->count())
        ->toBe(0);
});

test('a deleted user is rejected', function (): void {
    $administrator = cohortImportAdministrator();
    $targetCohort = cohortImportCohort();
    $student = cohortImportUser();

    $student->delete();

    $this->from(
        route('admin.cohort-imports.create'),
    )->actingAs($administrator)
        ->post(
            route('admin.cohort-imports.preview'),
            [
                'csv' => cohortImportCsv([
                    cohortImportCsvRow(
                        $student,
                        $targetCohort,
                    ),
                ]),
            ],
        )
        ->assertRedirect(
            route('admin.cohort-imports.create'),
        )
        ->assertSessionHasErrors([
            'csv' => 'CSV line 2 refers to a deleted account.',
        ]);
});

test('duplicate user rows are rejected', function (): void {
    $administrator = cohortImportAdministrator();
    $targetCohort = cohortImportCohort();
    $student = cohortImportUser();
    $row = cohortImportCsvRow(
        $student,
        $targetCohort,
    );

    $this->from(
        route('admin.cohort-imports.create'),
    )->actingAs($administrator)
        ->post(
            route('admin.cohort-imports.preview'),
            [
                'csv' => cohortImportCsv([
                    $row,
                    $row,
                ]),
            ],
        )
        ->assertRedirect(
            route('admin.cohort-imports.create'),
        )
        ->assertSessionHasErrors([
            'csv' => 'CSV line 3 duplicates an earlier user.',
        ]);
});

test('a role mismatch is rejected', function (): void {
    $administrator = cohortImportAdministrator();
    $targetCohort = cohortImportCohort();
    $student = cohortImportUser();

    $this->from(
        route('admin.cohort-imports.create'),
    )->actingAs($administrator)
        ->post(
            route('admin.cohort-imports.preview'),
            [
                'csv' => cohortImportCsv([
                    cohortImportCsvRow(
                        $student,
                        $targetCohort,
                        [
                            'role' => 'faculty',
                            'student_id' => '',
                        ],
                    ),
                ]),
            ],
        )
        ->assertRedirect(
            route('admin.cohort-imports.create'),
        )
        ->assertSessionHasErrors([
            'csv' => "CSV line 2 does not match the user's current role.",
        ]);
});

test('a student id mismatch is rejected', function (): void {
    $administrator = cohortImportAdministrator();
    $targetCohort = cohortImportCohort();
    $student = cohortImportUser();

    $this->from(
        route('admin.cohort-imports.create'),
    )->actingAs($administrator)
        ->post(
            route('admin.cohort-imports.preview'),
            [
                'csv' => cohortImportCsv([
                    cohortImportCsvRow(
                        $student,
                        $targetCohort,
                        [
                            'student_id' => 'WRONG-ID',
                        ],
                    ),
                ]),
            ],
        )
        ->assertRedirect(
            route('admin.cohort-imports.create'),
        )
        ->assertSessionHasErrors([
            'csv' => 'CSV line 2 has an incorrect student ID.',
        ]);
});

test('a missing cohort is rejected', function (): void {
    $administrator = cohortImportAdministrator();
    $student = cohortImportUser();

    $this->from(
        route('admin.cohort-imports.create'),
    )->actingAs($administrator)
        ->post(
            route('admin.cohort-imports.preview'),
            [
                'csv' => cohortImportCsv([
                    [
                        'email' => $student->email,
                        'student_id' => (string) $student->student_id,
                        'role' => 'student',
                        'cohort_name' => 'Missing Cohort',
                        'academic_year' => '2099-2100',
                    ],
                ]),
            ],
        )
        ->assertRedirect(
            route('admin.cohort-imports.create'),
        )
        ->assertSessionHasErrors([
            'csv' => 'CSV line 2 does not match an existing cohort.',
        ]);
});

test('an archived cohort is rejected', function (): void {
    $administrator = cohortImportAdministrator();
    $archivedCohort = cohortImportCohort(
        archived: true,
    );
    $student = cohortImportUser();

    $this->from(
        route('admin.cohort-imports.create'),
    )->actingAs($administrator)
        ->post(
            route('admin.cohort-imports.preview'),
            [
                'csv' => cohortImportCsv([
                    cohortImportCsvRow(
                        $student,
                        $archivedCohort,
                    ),
                ]),
            ],
        )
        ->assertRedirect(
            route('admin.cohort-imports.create'),
        )
        ->assertSessionHasErrors([
            'csv' => 'CSV line 2 targets an archived cohort.',
        ]);
});

test('an administrator cannot view another administrators preview', function (): void {
    $owner = cohortImportAdministrator();
    $otherAdministrator = cohortImportAdministrator();
    $targetCohort = cohortImportCohort();
    $student = cohortImportUser();
    $import = cohortImportPreview(
        $owner,
        [
            cohortImportCsvRow(
                $student,
                $targetCohort,
            ),
        ],
    );

    $this->actingAs($otherAdministrator)
        ->get(
            route(
                'admin.cohort-imports.show',
                ['import' => $import],
            ),
        )
        ->assertForbidden();
});

test('the current password is required to commit a preview', function (): void {
    $administrator = cohortImportAdministrator();
    $targetCohort = cohortImportCohort();
    $student = cohortImportUser();
    $import = cohortImportPreview(
        $administrator,
        [
            cohortImportCsvRow(
                $student,
                $targetCohort,
            ),
        ],
    );

    $this->from(
        route(
            'admin.cohort-imports.show',
            ['import' => $import],
        ),
    )->actingAs($administrator)
        ->post(
            route(
                'admin.cohort-imports.commit',
                ['import' => $import],
            ),
            cohortImportCommitPayload(
                $import,
                ['current_password' => 'wrong-password'],
            ),
        )
        ->assertRedirect(
            route(
                'admin.cohort-imports.show',
                ['import' => $import],
            ),
        )
        ->assertSessionHasErrors('current_password');

    expect($student->fresh()->cohort_id)
        ->toBeNull();
});

test('an expired preview cannot be committed', function (): void {
    $administrator = cohortImportAdministrator();
    $targetCohort = cohortImportCohort();
    $student = cohortImportUser();
    $import = cohortImportPreview(
        $administrator,
        [
            cohortImportCsvRow(
                $student,
                $targetCohort,
            ),
        ],
    );

    $import->forceFill([
        'expires_at' => now()->subSecond(),
    ])->save();

    $this->actingAs($administrator)
        ->post(
            route(
                'admin.cohort-imports.commit',
                ['import' => $import],
            ),
            cohortImportCommitPayload($import),
        )
        ->assertSessionHas(
            'error',
            'This preview expired. Upload the CSV again.',
        );

    expect($student->fresh()->cohort_id)
        ->toBeNull();
});

test('a stale lock version cannot commit a preview', function (): void {
    $administrator = cohortImportAdministrator();
    $targetCohort = cohortImportCohort();
    $student = cohortImportUser();
    $import = cohortImportPreview(
        $administrator,
        [
            cohortImportCsvRow(
                $student,
                $targetCohort,
            ),
        ],
    );

    $this->actingAs($administrator)
        ->post(
            route(
                'admin.cohort-imports.commit',
                ['import' => $import],
            ),
            cohortImportCommitPayload(
                $import,
                ['lock_version' => $import->lock_version + 1],
            ),
        )
        ->assertSessionHas(
            'error',
            'This preview is stale. Reload the page.',
        );

    expect($student->fresh()->cohort_id)
        ->toBeNull();
});

test('a user cohort change after preview prevents commit', function (): void {
    $administrator = cohortImportAdministrator();
    $originalCohort = cohortImportCohort(
        'Original Cohort',
        '2023-2024',
    );
    $intermediateCohort = cohortImportCohort(
        'Intermediate Cohort',
        '2024-2025',
    );
    $targetCohort = cohortImportCohort();
    $student = cohortImportUser(
        'student',
        $originalCohort,
    );
    $import = cohortImportPreview(
        $administrator,
        [
            cohortImportCsvRow(
                $student,
                $targetCohort,
            ),
        ],
    );

    $student->forceFill([
        'cohort_id' => $intermediateCohort->cohort_id,
    ])->save();

    $this->actingAs($administrator)
        ->post(
            route(
                'admin.cohort-imports.commit',
                ['import' => $import],
            ),
            cohortImportCommitPayload($import),
        )
        ->assertSessionHas(
            'error',
            'A user cohort changed after preview. Upload the CSV again.',
        );

    expect($student->fresh()->cohort_id)
        ->toBe($intermediateCohort->cohort_id);
});

test('a target cohort archived after preview prevents commit', function (): void {
    $administrator = cohortImportAdministrator();
    $targetCohort = cohortImportCohort();
    $student = cohortImportUser();
    $import = cohortImportPreview(
        $administrator,
        [
            cohortImportCsvRow(
                $student,
                $targetCohort,
            ),
        ],
    );

    $targetCohort->forceFill([
        'archived_at' => now(),
    ])->save();

    $this->actingAs($administrator)
        ->post(
            route(
                'admin.cohort-imports.commit',
                ['import' => $import],
            ),
            cohortImportCommitPayload($import),
        )
        ->assertSessionHas(
            'error',
            'A target cohort was archived or removed after preview.',
        );

    expect($student->fresh()->cohort_id)
        ->toBeNull();
});

test('a user with an official assessment cannot be moved', function (
    string $sessionType,
    string $researchPhase,
): void {
    $administrator = cohortImportAdministrator();
    $oldCohort = cohortImportCohort(
        'Old Cohort',
        '2024-2025',
    );
    $targetCohort = cohortImportCohort();
    $student = cohortImportUser(
        'student',
        $oldCohort,
    );
    $import = cohortImportPreview(
        $administrator,
        [
            cohortImportCsvRow(
                $student,
                $targetCohort,
            ),
        ],
    );

    AssessmentSession::query()->create([
        'user_id' => $student->user_id,
        'session_type' => $sessionType,
        'research_phase' => $researchPhase,
        'started_at' => now(),
    ]);

    $this->actingAs($administrator)
        ->post(
            route(
                'admin.cohort-imports.commit',
                ['import' => $import],
            ),
            cohortImportCommitPayload($import),
        )
        ->assertSessionHas(
            'error',
            'A cohort cannot be changed after an official research assessment has started.',
        );

    expect($student->fresh()->cohort_id)
        ->toBe($oldCohort->cohort_id);
})->with([
    'pre-test session' => [
        'diagnostic',
        'pre_test',
    ],
    'post-test session' => [
        'mock_board',
        'post_test',
    ],
]);

test('commit changes only the cohort assignment', function (): void {
    $administrator = cohortImportAdministrator();
    $oldCohort = cohortImportCohort(
        'Old Cohort',
        '2024-2025',
    );
    $targetCohort = cohortImportCohort();
    $student = cohortImportUser(
        'student',
        $oldCohort,
        [
            'first_name' => 'Maria',
            'middle_name' => 'Q',
            'last_name' => 'Santos',
            'email' => 'maria.santos@citirx.test',
            'student_id' => '2026-00123',
            'email_verified_at' => now()->subDay(),
            'total_xp' => 321,
        ],
    );
    $import = cohortImportPreview(
        $administrator,
        [
            cohortImportCsvRow(
                $student,
                $targetCohort,
            ),
        ],
    );

    $protectedFields = [
        'first_name',
        'middle_name',
        'last_name',
        'student_id',
        'email',
        'email_verified_at',
        'password',
        'role',
        'total_xp',
        'current_level',
    ];

    $before = collect($protectedFields)
        ->mapWithKeys(
            fn (string $field): array => [
                $field => $student->getRawOriginal($field),
            ],
        )
        ->all();

    $this->actingAs($administrator)
        ->post(
            route(
                'admin.cohort-imports.commit',
                ['import' => $import],
            ),
            cohortImportCommitPayload($import),
        )
        ->assertRedirect(
            route('admin.users.index'),
        )
        ->assertSessionHas(
            'status',
            '1 cohort assignment(s) updated; 0 unchanged.',
        );

    $student->refresh();

    $after = collect($protectedFields)
        ->mapWithKeys(
            fn (string $field): array => [
                $field => $student->getRawOriginal($field),
            ],
        )
        ->all();

    expect($student->cohort_id)
        ->toBe($targetCohort->cohort_id)
        ->and($after)
        ->toBe($before)
        ->and($import->fresh()->committed_at)
        ->not->toBeNull()
        ->and($import->fresh()->lock_version)
        ->toBe(2);
});

test('a committed import cannot be replayed', function (): void {
    $administrator = cohortImportAdministrator();
    $targetCohort = cohortImportCohort();
    $student = cohortImportUser();
    $import = cohortImportPreview(
        $administrator,
        [
            cohortImportCsvRow(
                $student,
                $targetCohort,
            ),
        ],
    );

    $this->actingAs($administrator)
        ->post(
            route(
                'admin.cohort-imports.commit',
                ['import' => $import],
            ),
            cohortImportCommitPayload($import),
        )
        ->assertRedirect(
            route('admin.users.index'),
        );

    $committedImport = $import->fresh();

    $this->actingAs($administrator)
        ->post(
            route(
                'admin.cohort-imports.commit',
                ['import' => $committedImport],
            ),
            cohortImportCommitPayload($committedImport),
        )
        ->assertSessionHas(
            'error',
            'This preview has already been committed.',
        );

    expect(
        AdminAuditLog::query()
            ->where(
                'action',
                'user.bulk_cohort_assigned',
            )
            ->count(),
    )->toBe(1);
});

test('audit data contains only cohort ids and excludes pii and credentials', function (): void {
    $administrator = cohortImportAdministrator();
    $oldCohort = cohortImportCohort(
        'Old Cohort',
        '2024-2025',
    );
    $targetCohort = cohortImportCohort();
    $student = cohortImportUser(
        'student',
        $oldCohort,
        [
            'first_name' => 'SensitiveFirst',
            'last_name' => 'SensitiveLast',
            'email' => 'private-student@citirx.test',
            'student_id' => 'PRIVATE-STUDENT-ID',
        ],
    );
    $import = cohortImportPreview(
        $administrator,
        [
            cohortImportCsvRow(
                $student,
                $targetCohort,
            ),
        ],
    );

    $this->actingAs($administrator)
        ->post(
            route(
                'admin.cohort-imports.commit',
                ['import' => $import],
            ),
            cohortImportCommitPayload($import),
        )
        ->assertRedirect(
            route('admin.users.index'),
        );

    $audit = AdminAuditLog::query()
        ->where(
            'action',
            'user.bulk_cohort_assigned',
        )
        ->firstOrFail();

    $serializedChanges = json_encode(
        $audit->changes,
        JSON_THROW_ON_ERROR,
    );

    expect($audit->changes)
        ->toHaveCount(1)
        ->and(array_keys($audit->changes))
        ->toBe(['cohort_id'])
        ->and($audit->changes['cohort_id'])
        ->toHaveCount(2)
        ->and($audit->changes['cohort_id']['old'])
        ->toBe($oldCohort->cohort_id)
        ->and($audit->changes['cohort_id']['new'])
        ->toBe($targetCohort->cohort_id)
        ->and($serializedChanges)
        ->not->toContain('password')
        ->not->toContain($student->email)
        ->not->toContain((string) $student->student_id)
        ->not->toContain($student->first_name)
        ->not->toContain($student->last_name);
});

test('more than five hundred rows are rejected', function (): void {
    $administrator = cohortImportAdministrator();
    $rows = [];

    foreach (range(1, 501) as $number) {
        $rows[] = [
            'email' => "bulk-{$number}@citirx.test",
            'student_id' => sprintf(
                'BULK-%05d',
                $number,
            ),
            'role' => 'student',
            'cohort_name' => 'Bulk Cohort',
            'academic_year' => '2025-2026',
        ];
    }

    $this->from(
        route('admin.cohort-imports.create'),
    )->actingAs($administrator)
        ->post(
            route('admin.cohort-imports.preview'),
            [
                'csv' => cohortImportCsv($rows),
            ],
        )
        ->assertRedirect(
            route('admin.cohort-imports.create'),
        )
        ->assertSessionHasErrors([
            'csv' => 'A maximum of 500 rows is allowed per import.',
        ]);

    expect(CohortAssignmentImport::query()->count())
        ->toBe(0);
});
