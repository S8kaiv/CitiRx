<?php

use App\Models\Question;
use App\Models\TosCompetency;
use App\Models\User;
use App\Services\ResearchFormAuditService;
use Database\Seeders\ResearchQuestionSeeder;
use Database\Seeders\TosSeeder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Command\Command;

beforeEach(function (): void {
    $this->withoutVite();
});

it('redirects guests from research form readiness', function (): void {
    $this->get(
        route('admin.research-forms.index'),
    )->assertRedirect(
        route('login'),
    );
});

it('blocks non administrators from research form readiness', function (
    string $role,
): void {
    $user = User::factory()->create([
        'role' => $role,
    ]);

    $this->actingAs($user)
        ->get(
            route('admin.research-forms.index'),
        )
        ->assertForbidden();
})->with([
    'student',
    'faculty',
]);

it('requires an administrator email to be verified', function (): void {
    $admin = User::factory()
        ->unverified()
        ->create([
            'role' => 'admin',
        ]);

    $this->actingAs($admin)
        ->get(
            route('admin.research-forms.index'),
        )
        ->assertRedirect(
            route('verification.notice'),
        );
});

it('allows a verified administrator to view both reports', function (): void {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $this->actingAs($admin)
        ->get(
            route('admin.research-forms.index'),
        )
        ->assertOk()
        ->assertSeeText(
            'Research Form Readiness',
        )
        ->assertSeeText(
            'Form A — Diagnostic',
        )
        ->assertSeeText(
            'Form B — Mock Board',
        )
        ->assertViewHas(
            'reports',
            fn (array $reports): bool => array_key_exists(
                Question::FORM_PRE_TEST_A,
                $reports,
            )
                && array_key_exists(
                    Question::FORM_POST_TEST_B,
                    $reports,
                ),
        );
});

it('reports seeded development forms without hiding placeholders', function (): void {
    $this->seed(TosSeeder::class);
    $this->seed(ResearchQuestionSeeder::class);

    $report = app(
        ResearchFormAuditService::class,
    )->audit(
        Question::FORM_PRE_TEST_A,
        requireValidation: true,
    );

    expect($report['active_questions'])->toBe(60)
        ->and($report['positions_complete'])->toBeTrue()
        ->and($report['domains_matching'])->toBe(6)
        ->and($report['competencies_matching'])
        ->toBe(count($report['competency_rows']))
        ->and($report['placeholders'])->toBe(60)
        ->and($report['fully_validated'])->toBe(0)
        ->and($report['status']['key'])
        ->toBe(
            ResearchFormAuditService::STATUS_DEVELOPMENT,
        )
        ->and($report['content_gate_ready'])->toBeFalse();
});

it('detects blueprint and domain allocation mismatches', function (): void {
    $this->seed(TosSeeder::class);
    $this->seed(ResearchQuestionSeeder::class);

    $question = Question::query()
        ->where(
            'research_form',
            Question::FORM_PRE_TEST_A,
        )
        ->orderBy('form_position')
        ->firstOrFail();

    $wrongCompetency = TosCompetency::query()
        ->where('competency_code', 'PB-01')
        ->firstOrFail();

    $question->update([
        'competency_id' => $wrongCompetency->competency_id,
    ]);

    $report = app(
        ResearchFormAuditService::class,
    )->audit(
        Question::FORM_PRE_TEST_A,
        requireValidation: false,
    );

    expect($report['domains_matching'])->toBeLessThan(6)
        ->and($report['competencies_matching'])
        ->toBeLessThan(
            count($report['competency_rows']),
        )
        ->and($report['structurally_valid'])->toBeFalse();
});

it('detects content answer and metadata integrity failures', function (): void {
    $this->seed(TosSeeder::class);
    $this->seed(ResearchQuestionSeeder::class);

    $questions = Question::query()
        ->where(
            'research_form',
            Question::FORM_PRE_TEST_A,
        )
        ->orderBy('form_position')
        ->limit(2)
        ->get();

    $first = $questions->firstOrFail();
    $second = $questions->last();

    $first->update([
        'question_text' => 'Duplicate normalized stem?',
        'hypercorrection_rationale' => null,
        'cognitive_level' => 'remember',
    ]);

    $second->update([
        'question_text' => 'Duplicate normalized stem!!!',
    ]);

    $first->choices()
        ->orderBy('choice_letter')
        ->firstOrFail()
        ->delete();

    $report = app(
        ResearchFormAuditService::class,
    )->audit(
        Question::FORM_PRE_TEST_A,
        requireValidation: false,
    );

    expect($report['invalid_choice_structures'])->toBe(1)
        ->and($report['missing_stems_or_choices'])->toBe(1)
        ->and($report['missing_rationales'])->toBe(1)
        ->and($report['invalid_cognitive_levels'])->toBe(1)
        ->and($report['duplicate_stem_groups'])->toBe(1)
        ->and($report['structurally_valid'])->toBeFalse();
});

it('rejects partial validation metadata', function (): void {
    $this->seed(TosSeeder::class);
    $this->seed(ResearchQuestionSeeder::class);

    $question = Question::query()
        ->where(
            'research_form',
            Question::FORM_PRE_TEST_A,
        )
        ->orderBy('form_position')
        ->firstOrFail();

    $question->forceFill([
        'research_validated_at' => now(),
    ])->save();

    $report = app(
        ResearchFormAuditService::class,
    )->audit(
        Question::FORM_PRE_TEST_A,
        requireValidation: true,
    );

    expect($report['partial_validation'])->toBe(1)
        ->and($report['validation_complete'])->toBeFalse()
        ->and($report['validation_errors'])->not->toBeEmpty();
});

it('distinguishes a ready content gate from a served locked form', function (): void {
    config()->set(
        'citirx.research.allow_unvalidated_forms',
        false,
    );

    $this->seed(TosSeeder::class);
    $this->seed(ResearchQuestionSeeder::class);

    $validator = User::factory()->create([
        'role' => 'faculty',
    ]);

    $questions = Question::query()
        ->where(
            'research_form',
            Question::FORM_PRE_TEST_A,
        )
        ->orderBy('form_position')
        ->get();

    foreach ($questions as $question) {
        $question->update([
            'question_text' => str_replace(
                '[DEVELOPMENT MOCK - NOT VALIDATED] ',
                '',
                $question->question_text,
            ),
        ]);
    }

    Question::query()
        ->where(
            'research_form',
            Question::FORM_PRE_TEST_A,
        )
        ->update([
            'research_validated_at' => now(),
            'research_validated_by' => $validator->user_id,
        ]);

    $service = app(
        ResearchFormAuditService::class,
    );

    $ready = $service->audit(
        Question::FORM_PRE_TEST_A,
        requireValidation: true,
    );

    expect($ready['status']['key'])
        ->toBe(
            ResearchFormAuditService::STATUS_READY,
        )
        ->and($ready['content_gate_ready'])->toBeTrue();

    DB::table('questions')
        ->where(
            'question_id',
            $questions->firstOrFail()->question_id,
        )
        ->update([
            'first_served_at' => now(),
        ]);

    $locked = $service->audit(
        Question::FORM_PRE_TEST_A,
        requireValidation: true,
    );

    expect($locked['served_questions'])->toBe(1)
        ->and($locked['status']['key'])
        ->toBe(
            ResearchFormAuditService::STATUS_LOCKED,
        );
});

it('uses the shared report for the CLI and administrator page', function (): void {
    $this->seed(TosSeeder::class);
    $this->seed(ResearchQuestionSeeder::class);

    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $expected = app(
        ResearchFormAuditService::class,
    )->audit(
        Question::FORM_PRE_TEST_A,
        requireValidation: true,
    );

    $this->actingAs($admin)
        ->get(
            route('admin.research-forms.index'),
        )
        ->assertOk()
        ->assertViewHas(
            'reports',
            fn (array $reports): bool => $reports[
                    Question::FORM_PRE_TEST_A
                ]['errors'] === $expected['errors'],
        );

    $this->artisan(
        'research:audit-forms',
        [
            '--form' => Question::FORM_PRE_TEST_A,
            '--require-validation' => true,
        ],
    )->assertExitCode(
        Command::FAILURE,
    );
});
