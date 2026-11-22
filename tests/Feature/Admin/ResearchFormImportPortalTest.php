<?php

use App\Models\AdminAuditLog;
use App\Models\PilotControl;
use App\Models\Question;
use App\Models\ResearchFormImport;
use App\Models\ResearchFormVersion;
use App\Models\User;
use App\Services\ResearchFormBlueprint;
use App\Services\ResearchFormVersionManagementService;
use App\Services\ResearchQuestionCsvService;
use Database\Seeders\ResearchQuestionSeeder;
use Database\Seeders\TosSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function ripAdministrator(): User
{
    return User::factory()->create(['role' => 'admin', 'password' => 'password']);
}

function ripActiveVersion(string $form = Question::FORM_PRE_TEST_A): ResearchFormVersion
{
    return ResearchFormVersion::query()->where('research_form', $form)->where('is_active', true)->sole();
}

function ripDraft(User $administrator, string $form = Question::FORM_PRE_TEST_A): ResearchFormVersion
{
    $source = ripActiveVersion($form);

    return app(ResearchFormVersionManagementService::class)->copyToDraft(
        $administrator, $source, $form.'_v2', $source->lock_version, 'Create an unserved draft for import testing.',
    );
}

function ripRow(array $overrides = []): array
{
    return array_replace([
        'research_form' => Question::FORM_PRE_TEST_A, 'form_position' => '1', 'domain_number' => '1',
        'competency_code' => 'PC-01', 'question_text' => 'A unique authored research question for position one?',
        'choice_a' => 'First answer', 'choice_b' => 'Second answer', 'choice_c' => 'Third answer', 'choice_d' => 'Fourth answer',
        'correct_choice' => 'A', 'rationale' => 'The first answer is correct for the stated reason.',
        'cognitive_level' => 'application', 'source_reference' => 'Original question authored for this test.',
        'copyright_permission' => 'permission confirmed',
    ], $overrides);
}

function ripCsv(?array $rows = null): UploadedFile
{
    $stream = fopen('php://temp', 'w+b');
    if ($stream === false) {
        throw new RuntimeException('Unable to create the research CSV fixture.');
    }
    fputcsv($stream, ResearchQuestionCsvService::HEADERS, ',', '"', '');
    foreach ($rows ?? [ripRow()] as $row) {
        fputcsv($stream, array_map(fn ($header) => $row[$header] ?? '', ResearchQuestionCsvService::HEADERS), ',', '"', '');
    }
    rewind($stream);
    $contents = stream_get_contents($stream);
    fclose($stream);
    if ($contents === false) {
        throw new RuntimeException('Unable to read the research CSV fixture.');
    }

    return UploadedFile::fake()->createWithContent('research-questions.csv', $contents);
}

function ripPreview(User $administrator, ResearchFormVersion $version, ?array $rows = null): ResearchFormImport
{
    test()->actingAs($administrator)->post(route('admin.research-form-imports.preview'), [
        'import_type' => ResearchFormImport::TYPE_CONTENT, 'form_version_id' => $version->form_version_id, 'csv' => ripCsv($rows),
    ])->assertRedirect(route('admin.research-form-imports.index'))->assertSessionHasNoErrors();

    return ResearchFormImport::query()->latest()->firstOrFail();
}

function ripCommitPayload(ResearchFormImport $import, array $selected = [2]): array
{
    return ['expected_lock_version' => $import->lock_version, 'selected_rows' => $selected];
}

beforeEach(function (): void {
    $this->withoutVite();
    Storage::fake('local');
    config()->set('citirx.research.allow_unvalidated_forms', false);
    $this->seed([TosSeeder::class, ResearchQuestionSeeder::class]);
});

it('redirects guests from the research import portal', function (): void {
    $this->get(route('admin.research-form-imports.index'))->assertRedirect(route('login'));
});

it('blocks non administrators from research imports', function (string $role): void {
    $this->actingAs(User::factory()->create(['role' => $role]))
        ->get(route('admin.research-form-imports.index'))->assertForbidden();
})->with(['student', 'faculty', 'dean']);

it('shows draft versions and the in app editor entry point', function (): void {
    $admin = ripAdministrator();
    $draft = ripDraft($admin);
    $this->actingAs($admin)->get(route('admin.research-form-imports.index'))->assertOk()
        ->assertSeeText('Research question import')->assertSeeText('Open in-app form editor')
        ->assertSeeText($draft->version_code);
    $this->get(route('admin.questions.index'))->assertOk()->assertSee(route('admin.research-form-imports.index'), false);
});

it('downloads the correct blueprint template for each form', function (string $form): void {
    $response = $this->actingAs(ripAdministrator())
        ->get(route('admin.research-form-imports.form-b-template', ['form' => $form]))
        ->assertOk()->assertDownload('citirx-'.$form.'-template.csv');
    $lines = preg_split('/\r?\n/', trim($response->streamedContent()));
    expect($lines)->toHaveCount(ResearchFormBlueprint::total($form) + 1);
    expect($lines[1])->toStartWith($form.',1,1,PC-01,');
})->with([Question::FORM_PRE_TEST_A, Question::FORM_POST_TEST_B]);

it('rejects malformed headers and removes the private upload', function (): void {
    $admin = ripAdministrator();
    $draft = ripDraft($admin);
    $this->actingAs($admin)->post(route('admin.research-form-imports.preview'), [
        'import_type' => 'content', 'form_version_id' => $draft->form_version_id,
        'csv' => UploadedFile::fake()->createWithContent('bad.csv', "wrong_header\ninvalid\n"),
    ])->assertSessionHasErrors('csv');
    expect(ResearchFormImport::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles('research-form-imports'))->toBe([]);
});

it('stores row previews privately without modifying the draft', function (): void {
    $admin = ripAdministrator();
    $draft = ripDraft($admin);
    $before = $draft->questions()->where('form_position', 1)->value('question_text');
    $import = ripPreview($admin, $draft);
    expect($import->status)->toBe(ResearchFormImport::STATUS_PREVIEWED)
        ->and($import->admin_id)->toBe($admin->user_id)
        ->and($import->form_version_id)->toBe($draft->form_version_id)
        ->and($import->preview_rows[0]['row_number'])->toBe(2)
        ->and($import->preview_rows[0]['errors'])->toBe([])
        ->and($draft->questions()->where('form_position', 1)->value('question_text'))->toBe($before);
    Storage::disk('local')->assertExists($import->storage_path);
});

it('previews invalid rows without letting them be selected for commit', function (): void {
    $admin = ripAdministrator();
    $draft = ripDraft($admin);
    $import = ripPreview($admin, $draft, [ripRow(['competency_code' => 'UNKNOWN'])]);
    expect($import->preview_rows[0]['errors'])->not->toBeEmpty();
    $this->get(route('admin.research-form-imports.index'))->assertOk()->assertSeeText('Select all eligible on this page');
    $this->post(route('admin.research-form-imports.commit', $import), ripCommitPayload($import))
        ->assertSessionHasErrors('selected_rows');
    expect($draft->questions()->where('question_text', ripRow()['question_text'])->count())->toBe(0);
});

it('does not accept the retired validation csv workflow', function (): void {
    $admin = ripAdministrator();
    $draft = ripDraft($admin);
    $this->actingAs($admin)->post(route('admin.research-form-imports.preview'), [
        'import_type' => ResearchFormImport::TYPE_VALIDATION, 'form_version_id' => $draft->form_version_id, 'csv' => ripCsv(),
    ])->assertSessionHasErrors('import_type');
    expect(ResearchFormImport::query()->count())->toBe(0);
});

it('requires an explicit selection before committing', function (): void {
    $admin = ripAdministrator();
    $draft = ripDraft($admin);
    $import = ripPreview($admin, $draft);
    $this->post(route('admin.research-form-imports.commit', $import), ['expected_lock_version' => $import->lock_version])
        ->assertSessionHasErrors('selected_rows');
    expect($import->fresh()->status)->toBe(ResearchFormImport::STATUS_PREVIEWED);
    Storage::disk('local')->assertExists($import->storage_path);
});

it('prevents another administrator from committing or discarding a valid preview', function (): void {
    $owner = ripAdministrator();
    $import = ripPreview($owner, ripDraft($owner));
    $this->actingAs(ripAdministrator())->post(route('admin.research-form-imports.commit', $import), ripCommitPayload($import))
        ->assertForbidden();
    $this->delete(route('admin.research-form-imports.destroy', $import))->assertForbidden();
    expect($import->fresh()->status)->toBe(ResearchFormImport::STATUS_PREVIEWED);
    Storage::disk('local')->assertExists($import->storage_path);
});

it('rejects a private upload changed after preview', function (): void {
    $admin = ripAdministrator();
    $draft = ripDraft($admin);
    $import = ripPreview($admin, $draft);
    $path = $import->storage_path;
    Storage::disk('local')->put($path, 'tampered content');
    $this->post(route('admin.research-form-imports.commit', $import), ripCommitPayload($import))->assertSessionHasErrors('csv');
    expect($import->fresh()->status)->toBe(ResearchFormImport::STATUS_FAILED);
    Storage::disk('local')->assertMissing($path);
});

it('rejects an expired preview', function (): void {
    $admin = ripAdministrator();
    $import = ripPreview($admin, ripDraft($admin));
    $import->update(['expires_at' => now()->subMinute()]);
    $this->post(route('admin.research-form-imports.commit', $import), ripCommitPayload($import))->assertSessionHasErrors('csv');
    expect($import->fresh()->status)->toBe(ResearchFormImport::STATUS_FAILED);
});

it('rejects a stale preview lock without invalidating the preview', function (): void {
    $admin = ripAdministrator();
    $import = ripPreview($admin, ripDraft($admin));
    $payload = ripCommitPayload($import);
    $payload['expected_lock_version']++;
    $this->post(route('admin.research-form-imports.commit', $import), $payload)->assertSessionHasErrors('csv');
    expect($import->fresh()->status)->toBe(ResearchFormImport::STATUS_PREVIEWED);
});

it('rejects a form changed since preview', function (): void {
    $admin = ripAdministrator();
    $draft = ripDraft($admin);
    $import = ripPreview($admin, $draft);
    $draft->increment('lock_version');
    $this->post(route('admin.research-form-imports.commit', $import), ripCommitPayload($import))->assertSessionHasErrors('csv');
    expect($draft->questions()->where('question_text', ripRow()['question_text'])->count())->toBe(0);
});

it('imports only selected eligible rows without approval or publication', function (): void {
    $admin = ripAdministrator();
    $draft = ripDraft($admin);
    $secondText = $draft->questions()->where('form_position', 2)->value('question_text');
    $import = ripPreview($admin, $draft, [
        ripRow(),
        ripRow(['form_position' => '2', 'question_text' => 'An unselected authored question?']),
    ]);
    $lock = $draft->lock_version;
    $path = $import->storage_path;
    $this->post(route('admin.research-form-imports.commit', $import), ripCommitPayload($import))
        ->assertRedirect(route('admin.research-forms.index'))->assertSessionHasNoErrors();
    $draft->refresh();
    expect($import->fresh()->status)->toBe(ResearchFormImport::STATUS_COMMITTED)
        ->and($import->fresh()->storage_path)->toBeNull()
        ->and($draft->lock_version)->toBe($lock + 2)
        ->and($draft->status)->toBe(ResearchFormVersion::STATUS_DRAFT)
        ->and($draft->dean_approved_at)->toBeNull()
        ->and($draft->is_active)->toBeFalse()
        ->and($draft->questions()->where('form_position', 1)->value('question_text'))->toBe(ripRow()['question_text'])
        ->and($draft->questions()->where('form_position', 2)->value('question_text'))->toBe($secondText)
        ->and($draft->questions()->whereNotNull('research_validated_at')->count())->toBe(0);
    Storage::disk('local')->assertMissing($path);
    $question = $draft->questions()->with('choices')->where('form_position', 1)->sole();
    expect($question->choices)->toHaveCount(4)
        ->and($question->choices->where('is_correct', true)->sole()->choice_letter)->toBe('A');
    $audit = AdminAuditLog::query()->where('action', 'research_form.content_imported')->sole();
    $json = json_encode($audit->changes, JSON_THROW_ON_ERROR);
    expect($audit->admin_id)->toBe($admin->user_id)->and($audit->target_id)->toBe($draft->form_version_id)
        ->and($json)->not->toContain($admin->email)->not->toContain('password')->not->toContain('research-questions.csv');
});

it('rejects replaying a committed preview without duplicating changes or audit', function (): void {
    $admin = ripAdministrator();
    $draft = ripDraft($admin);
    $import = ripPreview($admin, $draft);
    $payload = ripCommitPayload($import);
    $this->post(route('admin.research-form-imports.commit', $import), $payload)->assertSessionHasNoErrors();
    $lock = $draft->fresh()->lock_version;
    $this->post(route('admin.research-form-imports.commit', $import), $payload)->assertSessionHasErrors('csv');
    expect($draft->fresh()->lock_version)->toBe($lock)
        ->and(AdminAuditLog::query()->where('action', 'research_form.content_imported')->count())->toBe(1);
});

it('rolls back all selected rows when any selected row is invalid', function (): void {
    $admin = ripAdministrator();
    $draft = ripDraft($admin);
    $before = $draft->questions()->where('form_position', 1)->value('question_text');
    $import = ripPreview($admin, $draft, [ripRow(), ripRow(['form_position' => '2', 'competency_code' => 'UNKNOWN'])]);
    $this->post(route('admin.research-form-imports.commit', $import), ripCommitPayload($import, [2, 3]))
        ->assertSessionHasErrors('selected_rows');
    expect($draft->questions()->where('form_position', 1)->value('question_text'))->toBe($before)
        ->and(AdminAuditLog::query()->where('action', 'research_form.content_imported')->count())->toBe(0)
        ->and(AdminAuditLog::query()->where('action', 'research_form.question_saved')->count())->toBe(0);
});

it('rejects rows not contained in the preview', function (): void {
    $admin = ripAdministrator();
    $draft = ripDraft($admin);
    $import = ripPreview($admin, $draft);
    $this->post(route('admin.research-form-imports.commit', $import), ripCommitPayload($import, [999]))
        ->assertSessionHasErrors('selected_rows');
});

it('disables locked question rows and refuses forged selection', function (): void {
    $admin = ripAdministrator();
    $draft = ripDraft($admin);
    $question = $draft->questions()->where('form_position', 1)->sole();
    $question->forceFill(['first_served_at' => now()])->save();
    $import = ripPreview($admin, $draft);
    expect(implode(' ', $import->preview_rows[0]['errors']))->toContain('locked');
    $this->post(route('admin.research-form-imports.commit', $import), ripCommitPayload($import))
        ->assertSessionHasErrors('selected_rows');
    expect($question->fresh()->question_text)->not->toBe(ripRow()['question_text']);
});

it('blocks previews after official collection starts', function (): void {
    $admin = ripAdministrator();
    $draft = ripDraft($admin);
    PilotControl::query()->update(['official_collection_started_at' => now(), 'official_collection_started_by' => $admin->user_id]);
    $this->actingAs($admin)->post(route('admin.research-form-imports.preview'), [
        'import_type' => 'content', 'form_version_id' => $draft->form_version_id, 'csv' => ripCsv(),
    ])->assertSessionHasErrors('csv');
    expect(ResearchFormImport::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles('research-form-imports'))->toBe([]);
});

it('blocks committing a preview after official collection starts', function (): void {
    $admin = ripAdministrator();
    $draft = ripDraft($admin);
    $import = ripPreview($admin, $draft);
    PilotControl::query()->update(['official_collection_started_at' => now(), 'official_collection_started_by' => $admin->user_id]);
    $this->post(route('admin.research-form-imports.commit', $import), ripCommitPayload($import))->assertSessionHasErrors('csv');
    expect($draft->questions()->where('question_text', ripRow()['question_text'])->count())->toBe(0);
});
