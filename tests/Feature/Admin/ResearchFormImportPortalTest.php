<?php

use App\Models\AdminAuditLog;
use App\Models\PilotControl;
use App\Models\Question;
use App\Models\ResearchFormImport;
use App\Models\ResearchFormVersion;
use App\Models\User;
use App\Services\ResearchFormVersionManagementService;
use Database\Seeders\ResearchQuestionSeeder;
use Database\Seeders\TosSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

function ripAdministrator(): User
{
    return User::factory()->create([
        'role' => 'admin',
        'password' => 'password',
    ]);
}

function ripActiveVersion(): ResearchFormVersion
{
    return ResearchFormVersion::query()
        ->where(
            'research_form',
            Question::FORM_PRE_TEST_A,
        )
        ->where('is_active', true)
        ->where(
            'active_slot',
            Question::FORM_PRE_TEST_A,
        )
        ->sole();
}

function ripDraft(
    User $administrator,
): ResearchFormVersion {
    $source = ripActiveVersion();

    return app(
        ResearchFormVersionManagementService::class,
    )->copyToDraft(
        administrator: $administrator,
        source: $source,
        versionCode: 'pre_test_a_v2',
        expectedLockVersion: $source->lock_version,
        reason: 'Create a protected import-portal test draft.',
    );
}

function ripCsv(): UploadedFile
{
    return UploadedFile::fake()
        ->createWithContent(
            'pre_test_a_v2.csv',
            "research_form,form_position\npre_test_a,1\n",
        );
}

function ripPreview(
    User $administrator,
    ResearchFormVersion $version,
): ResearchFormImport {
    Artisan::shouldReceive('call')
        ->once()
        ->andReturn(0);

    Artisan::shouldReceive('output')
        ->once()
        ->andReturn(
            'Dry run passed; database changes were rolled back.',
        );

    test()
        ->actingAs($administrator)
        ->post(
            route(
                'admin.research-form-imports.preview',
            ),
            [
                'import_type' => ResearchFormImport::TYPE_CONTENT,

                'form_version_id' => $version->form_version_id,

                'csv' => ripCsv(),
            ],
        )
        ->assertSessionHasNoErrors();

    return ResearchFormImport::query()
        ->latest()
        ->firstOrFail();
}

beforeEach(function (): void {
    $this->withoutVite();

    Storage::fake('local');

    config()->set(
        'citirx.research.allow_unvalidated_forms',
        false,
    );

    config()->set(
        'citirx.research.import_preview_minutes',
        30,
    );

    $this->seed([
        TosSeeder::class,
        ResearchQuestionSeeder::class,
    ]);
});

it('redirects guests from the research import portal', function (): void {
    $this->get(
        route(
            'admin.research-form-imports.index',
        ),
    )->assertRedirect(
        route('login'),
    );
});

it('blocks non administrators from the research import portal', function (
    string $role,
): void {
    $user = User::factory()->create([
        'role' => $role,
    ]);

    $this->actingAs($user)
        ->get(
            route(
                'admin.research-form-imports.index',
            ),
        )
        ->assertForbidden();
})->with([
    'student',
    'faculty',
]);

it('allows an administrator to view importable versions', function (): void {
    $administrator = ripAdministrator();
    $draft = ripDraft($administrator);

    $this->actingAs($administrator)
        ->get(
            route(
                'admin.research-form-imports.index',
            ),
        )
        ->assertOk()
        ->assertSeeText(
            'Secure Research Import Portal',
        )
        ->assertSeeText(
            $draft->version_code,
        );
});

it('uses the existing importer to reject malformed content', function (): void {
    $administrator = ripAdministrator();
    $draft = ripDraft($administrator);

    $this->actingAs($administrator)
        ->post(
            route(
                'admin.research-form-imports.preview',
            ),
            [
                'import_type' => ResearchFormImport::TYPE_CONTENT,

                'form_version_id' => $draft->form_version_id,

                'csv' => UploadedFile::fake()
                    ->createWithContent(
                        'invalid.csv',
                        "wrong_header\ninvalid\n",
                    ),
            ],
        )
        ->assertSessionHasErrors('csv');

    expect(
        ResearchFormImport::query()->count(),
    )->toBe(0);

    expect(
        Storage::disk('local')->allFiles(
            'research-form-imports',
        ),
    )->toBe([]);
});

it('stores a successful preview privately without changing form content', function (): void {
    $administrator = ripAdministrator();
    $draft = ripDraft($administrator);

    $questionBefore = Question::query()
        ->where(
            'form_version_id',
            $draft->form_version_id,
        )
        ->where('form_position', 1)
        ->value('question_text');

    Artisan::shouldReceive('call')
        ->once()
        ->withArgs(
            function (
                string $command,
                array $arguments,
            ) use (
                $draft,
            ): bool {
                return $command
                        === 'research:import-form-content'
                    && $arguments['--form']
                        === Question::FORM_PRE_TEST_A
                    && $arguments['--form-version']
                        === $draft->version_code
                    && $arguments['--dry-run'] === true;
            },
        )
        ->andReturn(0);

    Artisan::shouldReceive('output')
        ->once()
        ->andReturn(
            'Dry run passed; database changes were rolled back.',
        );

    $this->actingAs($administrator)
        ->post(
            route(
                'admin.research-form-imports.preview',
            ),
            [
                'import_type' => ResearchFormImport::TYPE_CONTENT,

                'form_version_id' => $draft->form_version_id,

                'csv' => ripCsv(),
            ],
        )
        ->assertRedirect(
            route(
                'admin.research-form-imports.index',
            ),
        )
        ->assertSessionHasNoErrors();

    $import = ResearchFormImport::query()
        ->sole();

    expect($import->status)
        ->toBe(
            ResearchFormImport::STATUS_PREVIEWED,
        )
        ->and($import->admin_id)
        ->toBe($administrator->user_id)
        ->and($import->form_version_id)
        ->toBe($draft->form_version_id)
        ->and($import->storage_path)
        ->not->toBeNull();

    Storage::disk('local')->assertExists(
        $import->storage_path,
    );

    expect(
        Question::query()
            ->where(
                'form_version_id',
                $draft->form_version_id,
            )
            ->where('form_position', 1)
            ->value('question_text'),
    )->toBe($questionBefore);
});

it('requires the actual reviewer for validation imports', function (): void {
    $administrator = ripAdministrator();
    $validator = User::factory()->create([
        'role' => 'faculty',
    ]);

    $draft = ripDraft($administrator);

    /*
     * Version transitions are covered by their own test file.
     * This fixture places the target in the state required by
     * the validation-import command.
     */
    $draft->forceFill([
        'status' => ResearchFormVersion::STATUS_IN_REVIEW,
    ])->save();

    Artisan::shouldReceive('call')
        ->once()
        ->withArgs(
            function (
                string $command,
                array $arguments,
            ) use (
                $draft,
                $validator,
            ): bool {
                return $command
                        === 'research:import-validation'
                    && $arguments['--form-version']
                        === $draft->version_code
                    && $arguments['--validator-email']
                        === $validator->email
                    && $arguments['--dry-run'] === true;
            },
        )
        ->andReturn(0);

    Artisan::shouldReceive('output')
        ->once()
        ->andReturn(
            'Dry-run approval passed.',
        );

    $this->actingAs($administrator)
        ->post(
            route(
                'admin.research-form-imports.preview',
            ),
            [
                'import_type' => ResearchFormImport::TYPE_VALIDATION,

                'form_version_id' => $draft->form_version_id,

                'validator_id' => $validator->user_id,

                'csv' => ripCsv(),
            ],
        )
        ->assertSessionHasNoErrors();

    expect(
        ResearchFormImport::query()
            ->sole()
            ->validator_id,
    )->toBe($validator->user_id);
});

it('requires the current password before committing', function (): void {
    $administrator = ripAdministrator();
    $draft = ripDraft($administrator);
    $import = ripPreview(
        $administrator,
        $draft,
    );

    $this->actingAs($administrator)
        ->post(
            route(
                'admin.research-form-imports.commit',
                $import,
            ),
            [
                'expected_lock_version' => $import->lock_version,

                'reason' => 'Commit the reviewed research-form content.',

                'current_password' => 'incorrect-password',
            ],
        )
        ->assertSessionHasErrors(
            'current_password',
        );

    expect($import->fresh()->status)
        ->toBe(
            ResearchFormImport::STATUS_PREVIEWED,
        );

    Storage::disk('local')->assertExists(
        $import->storage_path,
    );
});

it('prevents another administrator from using a preview', function (): void {
    $owner = ripAdministrator();
    $otherAdministrator = ripAdministrator();

    $draft = ripDraft($owner);
    $import = ripPreview(
        $owner,
        $draft,
    );

    $this->actingAs($otherAdministrator)
        ->post(
            route(
                'admin.research-form-imports.commit',
                $import,
            ),
            [
                'expected_lock_version' => $import->lock_version,

                'reason' => 'Attempt to commit another administrator preview.',

                'current_password' => 'password',
            ],
        )
        ->assertForbidden();

    expect($import->fresh()->status)
        ->toBe(
            ResearchFormImport::STATUS_PREVIEWED,
        );
});

it('rejects a private upload changed after preview', function (): void {
    $administrator = ripAdministrator();
    $draft = ripDraft($administrator);
    $import = ripPreview(
        $administrator,
        $draft,
    );

    $path = $import->storage_path;

    Storage::disk('local')->put(
        $path,
        'tampered content',
    );

    $this->actingAs($administrator)
        ->post(
            route(
                'admin.research-form-imports.commit',
                $import,
            ),
            [
                'expected_lock_version' => $import->lock_version,

                'reason' => 'Commit the approved research-form content.',

                'current_password' => 'password',
            ],
        )
        ->assertSessionHasErrors('csv');

    expect($import->fresh()->status)
        ->toBe(
            ResearchFormImport::STATUS_FAILED,
        );

    Storage::disk('local')->assertMissing(
        $path,
    );
});

it('commits once increments the version lock and writes a safe audit', function (): void {
    $administrator = ripAdministrator();
    $draft = ripDraft($administrator);

    Artisan::shouldReceive('call')
        ->twice()
        ->andReturn(0, 0);

    Artisan::shouldReceive('output')
        ->twice()
        ->andReturn(
            'Dry run passed.',
            'Imported 60 unvalidated questions.',
        );

    $this->actingAs($administrator)
        ->post(
            route(
                'admin.research-form-imports.preview',
            ),
            [
                'import_type' => ResearchFormImport::TYPE_CONTENT,

                'form_version_id' => $draft->form_version_id,

                'csv' => ripCsv(),
            ],
        )
        ->assertSessionHasNoErrors();

    $import = ResearchFormImport::query()
        ->sole();

    $storedPath = $import->storage_path;
    $oldVersionLock = $draft->fresh()->lock_version;

    $this->actingAs($administrator)
        ->post(
            route(
                'admin.research-form-imports.commit',
                $import,
            ),
            [
                'expected_lock_version' => $import->lock_version,

                'reason' => 'Import the faculty-reviewed Form A content.',

                'current_password' => 'password',
            ],
        )
        ->assertRedirect(
            route(
                'admin.research-forms.index',
            ),
        )
        ->assertSessionHasNoErrors();

    $import->refresh();
    $draft->refresh();

    expect($import->status)
        ->toBe(
            ResearchFormImport::STATUS_COMMITTED,
        )
        ->and($import->committed_at)
        ->not->toBeNull()
        ->and($import->storage_path)
        ->toBeNull()
        ->and($draft->lock_version)
        ->toBe($oldVersionLock + 1);

    Storage::disk('local')->assertMissing(
        $storedPath,
    );

    $audit = AdminAuditLog::query()
        ->where(
            'action',
            'research_form.content_imported',
        )
        ->latest()
        ->firstOrFail();

    $serializedAudit = json_encode(
        $audit->changes,
        JSON_THROW_ON_ERROR,
    );

    expect($serializedAudit)
        ->not->toContain(
            $administrator->email,
        )
        ->not->toContain(
            'password',
        )
        ->not->toContain(
            'pre_test_a_v2.csv',
        )
        ->not->toContain(
            'research-form-imports',
        );

    /*
     * Replaying a committed preview must fail without running
     * the import command a third time.
     */
    $this->actingAs($administrator)
        ->post(
            route(
                'admin.research-form-imports.commit',
                $import,
            ),
            [
                'expected_lock_version' => $import->lock_version,

                'reason' => 'Attempt to replay the completed import.',

                'current_password' => 'password',
            ],
        )
        ->assertSessionHasErrors('csv');
});

it('blocks imports after official collection starts', function (): void {
    $administrator = ripAdministrator();
    $draft = ripDraft($administrator);

    PilotControl::query()->update([
        'official_collection_started_at' => now(),

        'official_collection_started_by' => $administrator->user_id,
    ]);

    Artisan::shouldReceive('call')->never();

    $this->actingAs($administrator)
        ->post(
            route(
                'admin.research-form-imports.preview',
            ),
            [
                'import_type' => ResearchFormImport::TYPE_CONTENT,

                'form_version_id' => $draft->form_version_id,

                'csv' => ripCsv(),
            ],
        )
        ->assertSessionHasErrors('csv');

    expect(
        ResearchFormImport::query()->count(),
    )->toBe(0);

    expect(
        Storage::disk('local')->allFiles(
            'research-form-imports',
        ),
    )->toBe([]);
});
