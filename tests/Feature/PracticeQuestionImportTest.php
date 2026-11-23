<?php

use App\Models\PracticeQuestionImport;
use App\Models\Question;
use App\Models\QuestionEdit;
use App\Models\TosCompetency;
use App\Models\User;
use App\Services\PracticeQuestionImportService;
use App\Services\PracticeService;
use Database\Seeders\TosSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(TosSeeder::class);
});

function practiceImportRow(array $overrides = []): array
{
    return array_replace([
        'competency_code' => 'PC-01', 'question_text' => "Which reviewed concept applies, and why?\nChoose one answer.",
        'choice_a' => 'Answer Alpha', 'choice_b' => 'Answer Bravo', 'choice_c' => 'Answer Charlie', 'choice_d' => 'Answer Delta',
        'correct_choice' => 'B', 'rationale' => 'Answer Bravo matches the original lesson.', 'cognitive_level' => '',
        'source_reference' => 'Original fixture content, written for automated checks.', 'copyright_permission' => 'permission confirmed',
    ], $overrides);
}

function practiceImportFile(array $rows, ?array $headers = null): UploadedFile
{
    $headers ??= PracticeQuestionImportService::HEADERS;
    $stream = fopen('php://temp', 'w+b');
    fwrite($stream, "\xEF\xBB\xBF");
    fputcsv($stream, $headers, ',', '"', '');
    foreach ($rows as $row) {
        fputcsv($stream, array_values($row), ',', '"', '');
    }
    rewind($stream);
    $contents = stream_get_contents($stream);
    fclose($stream);

    return UploadedFile::fake()->createWithContent('practice.csv', $contents);
}

it('requires authentication and verified email for practice CSV imports', function (string $role): void {
    $prefix = $role.'.practice-questions.imports';
    $this->get(route($prefix.'.create'))->assertRedirect(route('login'));
    $this->post(route($prefix.'.preview'), ['csv' => practiceImportFile([practiceImportRow()])])->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create(['role' => $role, 'email_verified_at' => null]));
    $this->get(route($prefix.'.template'))->assertRedirect(route('verification.notice'));
    $this->post(route($prefix.'.preview'), ['csv' => practiceImportFile([practiceImportRow()])])->assertRedirect(route('verification.notice'));
})->with(['admin', 'dean']);

it('limits every practice CSV endpoint to the Admin and Dean', function (string $role): void {
    $owner = User::factory()->create(['role' => 'admin']);
    $import = app(PracticeQuestionImportService::class)->preview($owner, practiceImportFile([practiceImportRow()]));
    $this->actingAs(User::factory()->create(['role' => $role]));
    foreach (['admin', 'dean'] as $staff) {
        $prefix = $staff.'.practice-questions.imports';
        $this->get(route($prefix.'.create'))->assertForbidden();
        $this->get(route($prefix.'.template'))->assertForbidden();
        $this->get(route($prefix.'.show', $import))->assertForbidden();
        $this->post(route($prefix.'.preview'), ['csv' => practiceImportFile([practiceImportRow()])])->assertForbidden();
        $this->post(route($prefix.'.commit', $import), ['selected_rows' => [2]])->assertForbidden();
    }
    expect(Question::query()->count())->toBe(0);
})->with(['student', 'faculty']);

it('lets either staff role download the template and preview then import only selected questions once', function (string $role): void {
    $owner = User::factory()->create(['role' => $role]);
    $prefix = $role.'.practice-questions';
    $this->actingAs($owner)->get(route($prefix.'.index'))->assertOk()->assertSeeText('Import practice CSV');
    $this->get(route($prefix.'.imports.create'))->assertOk()->assertSeeText('Competency reference');
    $this->get(route($prefix.'.imports.template'))->assertOk()->assertDownload('practice-question-import-template.csv')
        ->assertStreamedContent("\xEF\xBB\xBF".implode(',', PracticeQuestionImportService::HEADERS)."\n");
    $this->post(route($prefix.'.imports.preview'), ['csv' => practiceImportFile([
        practiceImportRow(['correct_choice' => 'b']), practiceImportRow(['question_text' => 'Another valid question']),
        practiceImportRow(['question_text' => 'Invalid source rights', 'copyright_permission' => 'pending']),
    ])])->assertSessionHasNoErrors();
    $import = PracticeQuestionImport::query()->sole();
    expect($import->preview_rows[0]['errors'])->toBe([])->and($import->preview_rows[0]['payload']['correct_choice'])->toBe('B')
        ->and($import->preview_rows[2]['errors'])->not->toBeEmpty()->and($import->canCommit())->toBeTrue()
        ->and(Question::query()->count())->toBe(0);
    $this->get(route($prefix.'.imports.show', $import))->assertOk()->assertSeeText('2 of 3 rows are eligible.')
        ->assertSee('data-bulk', false)->assertSee('data-confirm=', false)->assertSeeText('Review choices, answer, rationale, and source');
    $this->post(route($prefix.'.imports.commit', $import), ['selected_rows' => [2], 'preview_rows' => [], 'research_form' => 'post_test_b'])
        ->assertRedirect(route($prefix.'.index'))->assertSessionHasNoErrors();
    $question = Question::query()->sole();
    expect($question->question_text)->toBe(practiceImportRow()['question_text'])->and($question->is_active)->toBeTrue()
        ->and($question->research_form)->toBeNull()->and($question->form_version_id)->toBeNull()->and($question->is_diagnostic_pool)->toBeFalse()
        ->and($question->choices()->count())->toBe(4)->and($question->choices()->where('is_correct', true)->sole()->choice_letter)->toBe('B')
        ->and(QuestionEdit::query()->sole()->editor_id)->toBe($owner->user_id)
        ->and($import->fresh()->committed_at)->not->toBeNull()->and($import->fresh()->preview_rows)->toBeNull();
    $this->from(route($prefix.'.imports.show', $import))->post(route($prefix.'.imports.commit', $import), ['selected_rows' => [2]])
        ->assertSessionHasErrors('csv');
    expect(Question::query()->count())->toBe(1)->and(QuestionEdit::query()->count())->toBe(1);
    $this->get(route($prefix.'.imports.show', $import))->assertOk()->assertSeeText('This preview has already been imported.');
    $student = User::factory()->create(['role' => 'student', 'is_diagnostic_completed' => true]);
    $practice = app(PracticeService::class);
    expect($practice->pickNextQuestion($practice->startSession($student, 10))?->question_id)->toBe($question->question_id);
})->with(['admin', 'dean']);

it('shows row errors and rejects a selection containing invalid or forged rows atomically', function (array $selection): void {
    $owner = User::factory()->create(['role' => 'dean']);
    $import = app(PracticeQuestionImportService::class)->preview($owner, practiceImportFile([
        practiceImportRow(), practiceImportRow(['question_text' => 'Missing source', 'source_reference' => '']),
    ]));
    $this->actingAs($owner)->from(route('dean.practice-questions.imports.show', $import))
        ->post(route('dean.practice-questions.imports.commit', $import), ['selected_rows' => $selection])
        ->assertSessionHasErrors();
    expect(Question::query()->count())->toBe(0)->and(QuestionEdit::query()->count())->toBe(0)
        ->and($import->fresh()->committed_at)->toBeNull();
})->with([[[2, 3]], [[2, 999]], [[2, 2]], [[]]]);

it('keeps a preview private to its uploader', function (): void {
    $owner = User::factory()->create(['role' => 'dean']);
    $import = app(PracticeQuestionImportService::class)->preview($owner, practiceImportFile([practiceImportRow()]));
    $this->actingAs(User::factory()->create(['role' => 'dean']))
        ->get(route('dean.practice-questions.imports.show', $import))->assertForbidden();
    $this->post(route('dean.practice-questions.imports.commit', $import), ['selected_rows' => [2]])->assertForbidden();
    expect(Question::query()->count())->toBe(0);
});

it('redisplays inline errors after malformed selection input', function (): void {
    $owner = User::factory()->create(['role' => 'dean']);
    $import = app(PracticeQuestionImportService::class)->preview($owner, practiceImportFile([practiceImportRow()]));
    $previewUrl = route('dean.practice-questions.imports.show', $import);
    // Carry the browser's session across the rejected POST and its redirected GET.
    $this->withSession([])->withCookie(config('session.cookie'), session()->getId());
    $this->actingAs($owner)->from($previewUrl)
        ->post(route('dean.practice-questions.imports.commit', $import), ['selected_rows' => '2'])
        ->assertRedirect($previewUrl)->assertSessionHasErrors('selected_rows');
    $message = session('errors')->first('selected_rows');
    $this->get($previewUrl)->assertOk()->assertSeeText($message)->assertSee('role="alert"', false);
    expect(Question::query()->count())->toBe(0);
});

it('rejects expired previews and can prune their private content', function (): void {
    $owner = User::factory()->create(['role' => 'admin']);
    $import = app(PracticeQuestionImportService::class)->preview($owner, practiceImportFile([practiceImportRow()]));
    $this->travel(31)->minutes();
    $this->actingAs($owner)->get(route('admin.practice-questions.imports.show', $import))->assertOk()->assertSeeText('This preview has expired.');
    $this->post(route('admin.practice-questions.imports.commit', $import), ['selected_rows' => [2]])->assertSessionHasErrors('csv');
    expect(Question::query()->count())->toBe(0)->and((new PracticeQuestionImport)->prunable()->whereKey($import->import_id)->exists())->toBeTrue();
});

it('revalidates competencies at commit time without leaving partial content', function (): void {
    $owner = User::factory()->create(['role' => 'admin']);
    $import = app(PracticeQuestionImportService::class)->preview($owner, practiceImportFile([
        practiceImportRow(), practiceImportRow(['question_text' => 'Another question', 'competency_code' => 'PC-02']),
    ]));
    TosCompetency::query()->where('competency_code', 'PC-02')->delete();
    $this->actingAs($owner)->post(route('admin.practice-questions.imports.commit', $import), ['selected_rows' => [2, 3]])
        ->assertSessionHasErrors('selected_rows');
    expect(Question::query()->count())->toBe(0)->and(QuestionEdit::query()->count())->toBe(0);
});

it('validates CSV content using the manual authoring limits', function (): void {
    $owner = User::factory()->create(['role' => 'admin']);
    $import = app(PracticeQuestionImportService::class)->preview($owner, practiceImportFile([
        practiceImportRow(['question_text' => str_repeat('x', 2001)]),
        practiceImportRow(['question_text' => 'Unknown competency', 'competency_code' => 'INVALID']),
        practiceImportRow(['question_text' => 'Same choices', 'choice_a' => ' same  answer ', 'choice_b' => 'Same answer']),
        practiceImportRow(['question_text' => 'Bad answer', 'correct_choice' => 'E']),
        practiceImportRow(['question_text' => 'Missing rationale', 'rationale' => '']),
        practiceImportRow(['question_text' => 'Bad cognitive level', 'cognitive_level' => 'other']),
        practiceImportRow(['question_text' => 'Repeated stem']), practiceImportRow(['question_text' => '  repeated   STEM ']),
        ['wrong', 'column count'],
    ]));
    foreach ([0, 1, 2, 3, 4, 5, 7, 8] as $index) {
        expect($import->preview_rows[$index]['errors'])->not->toBeEmpty();
    }
    expect($import->preview_rows[6]['errors'])->toBe([])->and($import->preview_rows[8]['payload'])->toBeNull();
});

it('rejects bad headers, empty CSVs, non UTF-8 content, null bytes and oversized batches', function (): void {
    $owner = User::factory()->create(['role' => 'admin']);
    $service = app(PracticeQuestionImportService::class);
    foreach ([
        practiceImportFile([practiceImportRow()], ['wrong_header']), practiceImportFile([]),
        UploadedFile::fake()->createWithContent('practice.csv', "\xFF\xFEbad encoding"),
        UploadedFile::fake()->createWithContent('practice.csv', "null\0byte"),
        UploadedFile::fake()->createWithContent('practice.csv', str_repeat('a', 2097153)),
        practiceImportFile(array_fill(0, 201, practiceImportRow())),
    ] as $file) {
        expect(fn () => $service->preview($owner, $file))->toThrow(ValidationException::class);
    }
    expect(PracticeQuestionImport::query()->count())->toBe(0)->and(Question::query()->count())->toBe(0);
});

it('uses the same uniqueness rules as manual entry for numeric answer choices', function (): void {
    $owner = User::factory()->create(['role' => 'admin']);
    $import = app(PracticeQuestionImportService::class)->preview($owner, practiceImportFile([
        practiceImportRow(['choice_a' => '1', 'choice_b' => '1.0', 'choice_c' => '2', 'choice_d' => '3']),
    ]));
    expect($import->preview_rows[0]['errors'])->toContain('Every answer choice must have different text.');
    $this->actingAs($owner)->post(route('admin.practice-questions.imports.commit', $import), ['selected_rows' => [2]])
        ->assertSessionHasErrors('selected_rows');
    expect(Question::query()->count())->toBe(0);
});

it('rejects missing and incorrectly named uploads at the HTTP boundary', function (): void {
    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->post(route('admin.practice-questions.imports.preview'))->assertSessionHasErrors('csv');
    $this->post(route('admin.practice-questions.imports.preview'), ['csv' => UploadedFile::fake()->createWithContent('practice.txt', 'text')])
        ->assertSessionHasErrors('csv');
});

it('escapes imported question text and source references in the staff preview', function (): void {
    $owner = User::factory()->create(['role' => 'dean']);
    $import = app(PracticeQuestionImportService::class)->preview($owner, practiceImportFile([
        practiceImportRow(['question_text' => '<script>alert("question")</script>', 'source_reference' => '<script>alert("source")</script>']),
    ]));
    $this->actingAs($owner)->get(route('dean.practice-questions.imports.show', $import))->assertOk()
        ->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(', false);
});

it('also enforces staff role and row selection rules in the import service', function (): void {
    $owner = User::factory()->create(['role' => 'admin']);
    $service = app(PracticeQuestionImportService::class);
    $import = $service->preview($owner, practiceImportFile([practiceImportRow()]));
    expect(fn () => $service->commit($owner, $import, [2, 2]))->toThrow(ValidationException::class);
    $faculty = User::factory()->create(['role' => 'faculty']);
    expect(fn () => $service->preview($faculty, practiceImportFile([practiceImportRow()])))->toThrow(HttpException::class);
    expect(fn () => $service->commit($faculty, $import, [2]))->toThrow(HttpException::class);
    expect(Question::query()->count())->toBe(0);
});
