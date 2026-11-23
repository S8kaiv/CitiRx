<?php

use App\Models\Question;
use App\Models\QuestionEdit;
use App\Models\TosCompetency;
use App\Models\User;
use App\Services\PracticeService;
use App\Services\QuestionEditService;
use Database\Seeders\TosSeeder;

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(TosSeeder::class);
});

function practiceAuthoringPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'competency_id' => TosCompetency::query()->where('competency_code', 'PC-01')->firstOrFail()->competency_id,
        'question_text' => 'Which option matches the reviewed concept?',
        'choices' => ['A' => ['text' => 'Answer Alpha'], 'B' => ['text' => 'Answer Bravo'], 'C' => ['text' => 'Answer Charlie'], 'D' => ['text' => 'Answer Delta']],
        'correct_choice' => 'B',
        'hypercorrection_rationale' => 'Answer Bravo matches the concept explained in the original lesson.',
        'cognitive_level' => Question::COGNITIVE_COMPREHENSION,
        'source_reference' => 'Original content authored solely for this automated fixture.',
        'copyright_permission' => 'permission confirmed',
    ], $overrides);
}

function authoredPracticeQuestion(): Question
{
    return app(QuestionEditService::class)->createPractice(User::factory()->create(['role' => 'admin']), practiceAuthoringPayload());
}

it('requires login and verified email for practice question authoring', function (string $role): void {
    $question = authoredPracticeQuestion();
    $prefix = $role.'.practice-questions';
    foreach (['index', 'create'] as $action) {
        $this->get(route($prefix.'.'.$action))->assertRedirect(route('login'));
    }
    $this->get(route($prefix.'.show', $question))->assertRedirect(route('login'));
    $this->post(route($prefix.'.store'), practiceAuthoringPayload())->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create(['role' => $role, 'email_verified_at' => null]));
    $this->get(route($prefix.'.create'))->assertRedirect(route('verification.notice'));
    $this->post(route($prefix.'.store'), practiceAuthoringPayload())->assertRedirect(route('verification.notice'));
})->with(['admin', 'dean']);

it('does not give students or Faculty practice question management access', function (string $role): void {
    $question = authoredPracticeQuestion();
    $this->actingAs(User::factory()->create(['role' => $role]));
    foreach (['admin', 'dean'] as $staff) {
        $prefix = $staff.'.practice-questions';
        $this->get(route($prefix.'.index'))->assertForbidden();
        $this->get(route($prefix.'.create'))->assertForbidden();
        $this->get(route($prefix.'.show', $question))->assertForbidden();
        $this->post(route($prefix.'.store'), practiceAuthoringPayload())->assertForbidden();
    }
    expect(Question::query()->count())->toBe(1);
})->with(['student', 'faculty']);

it('lets the Admin and Dean create a complete practice item with a creation audit', function (string $role): void {
    $editor = User::factory()->create(['role' => $role]);
    $prefix = $role.'.practice-questions';
    $this->actingAs($editor)->get(route($prefix.'.create'))->assertOk()->assertSeeText('Add practice question');
    $response = $this->post(route($prefix.'.store'), practiceAuthoringPayload());
    $question = Question::query()->sole();
    $response->assertRedirect(route($prefix.'.show', $question))->assertSessionHasNoErrors();
    expect($question->research_form)->toBeNull()->and($question->form_version_id)->toBeNull()
        ->and($question->is_diagnostic_pool)->toBeFalse()->and($question->is_active)->toBeTrue()
        ->and($question->edit_version)->toBe(1)->and($question->choices()->count())->toBe(4)
        ->and($question->choices()->where('is_correct', true)->sole()->choice_letter)->toBe('B');
    $audit = QuestionEdit::query()->sole();
    expect($audit->editor_id)->toBe($editor->user_id)->and($audit->action)->toBe('created')
        ->and($audit->version_before)->toBe(0)->and($audit->version_after)->toBe(1)
        ->and($audit->changes['correct_choice']['new'])->toBe('B');
    $this->get(route($prefix.'.show', $question))->assertOk()
        ->assertSeeText('Correct answer')->assertSeeText($question->hypercorrection_rationale)->assertSeeText($question->source_reference);
})->with(['admin', 'dean']);

it('validates required practice content and source rights inline', function (array $overrides, string $field): void {
    $this->actingAs(User::factory()->create(['role' => 'dean']))
        ->from(route('dean.practice-questions.create'))
        ->post(route('dean.practice-questions.store'), practiceAuthoringPayload($overrides))
        ->assertRedirect(route('dean.practice-questions.create'))->assertSessionHasErrors($field);
    expect(Question::query()->count())->toBe(0)->and(QuestionEdit::query()->count())->toBe(0);
})->with([
    [['question_text' => ''], 'question_text'],
    [['hypercorrection_rationale' => ''], 'hypercorrection_rationale'],
    [['source_reference' => ''], 'source_reference'],
    [['copyright_permission' => 'pending'], 'copyright_permission'],
    [['competency_id' => '00000000-0000-4000-8000-000000000001'], 'competency_id'],
    [['choices' => ['A' => ['text' => '']]], 'choices.A.text'],
    [['correct_choice' => 'E'], 'correct_choice'],
]);

it('rejects duplicate choices without leaving partial questions or audit records', function (): void {
    $this->actingAs(User::factory()->create(['role' => 'admin']))
        ->from(route('admin.practice-questions.create'))
        ->post(route('admin.practice-questions.store'), practiceAuthoringPayload([
            'choices' => ['A' => ['text' => 'Same  answer'], 'B' => ['text' => ' same answer ']],
        ]))->assertRedirect(route('admin.practice-questions.create'))->assertSessionHasErrors('choices');
    expect(Question::query()->count())->toBe(0)->and(QuestionEdit::query()->count())->toBe(0);
});

it('ignores posted research flags, validation metadata, activity flags and choice correctness', function (): void {
    $editor = User::factory()->create(['role' => 'dean']);
    $this->actingAs($editor)->post(route('dean.practice-questions.store'), practiceAuthoringPayload([
        'research_form' => Question::FORM_PRE_TEST_A, 'form_position' => 1, 'form_version_id' => fake()->uuid(),
        'research_validated_at' => now()->toDateTimeString(), 'research_validated_by' => $editor->user_id,
        'is_diagnostic_pool' => true, 'is_active' => false, 'first_served_at' => now()->toDateTimeString(), 'edit_version' => 200,
        'choices' => ['A' => ['is_correct' => true]],
    ]))->assertSessionHasNoErrors();
    $question = Question::query()->sole();
    expect($question->research_form)->toBeNull()->and($question->form_version_id)->toBeNull()
        ->and($question->form_position)->toBeNull()->and($question->research_validated_at)->toBeNull()
        ->and($question->research_validated_by)->toBeNull()->and($question->first_served_at)->toBeNull()
        ->and($question->edit_version)->toBe(1)->and($question->is_active)->toBeTrue()
        ->and($question->is_diagnostic_pool)->toBeFalse()
        ->and($question->choices()->where('is_correct', true)->sole()->choice_letter)->toBe('B');
});

it('keeps research items out of the practice authoring list and detail routes', function (): void {
    $practice = authoredPracticeQuestion();
    $research = Question::query()->create([
        'competency_id' => $practice->competency_id, 'question_text' => 'Private research form question',
        'research_form' => Question::FORM_PRE_TEST_A, 'form_position' => 1, 'is_active' => true,
    ]);
    $this->actingAs(User::factory()->create(['role' => 'dean']))
        ->get(route('dean.practice-questions.index'))->assertOk()->assertSeeText($practice->question_text)
        ->assertDontSeeText($research->question_text);
    $this->get(route('dean.practice-questions.show', $research))->assertNotFound();
});

it('makes an authored item available to the existing practice service and preserves served-content locks', function (): void {
    $question = authoredPracticeQuestion();
    $student = User::factory()->create(['role' => 'student', 'is_diagnostic_completed' => true]);
    $practice = app(PracticeService::class);
    $session = $practice->startSession($student, 10);
    $served = $practice->pickNextQuestion($session);
    expect($served?->question_id)->toBe($question->question_id)->and($question->fresh()->first_served_at)->not->toBeNull();
    expect(fn () => app(QuestionEditService::class)->updateContent(
        User::factory()->create(['role' => 'admin']), $question->fresh(), practiceAuthoringPayload(), 1, null,
    ))->toThrow(LogicException::class);
});

it('escapes question and source content in the staff preview', function (): void {
    $question = app(QuestionEditService::class)->createPractice(User::factory()->create(['role' => 'admin']), practiceAuthoringPayload([
        'question_text' => '<script>alert("question")</script>', 'source_reference' => '<script>alert("source")</script>',
    ]));
    $this->actingAs(User::factory()->create(['role' => 'dean']))
        ->get(route('dean.practice-questions.show', $question))->assertOk()
        ->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(', false);
});

it('does not expose Faculty authoring through the shared service', function (): void {
    expect(fn () => app(QuestionEditService::class)->createPractice(User::factory()->create(['role' => 'faculty']), practiceAuthoringPayload()))
        ->toThrow(LogicException::class);
    expect(Question::query()->count())->toBe(0);
});
