<?php

use App\Models\TosCompetency;
use App\Models\User;
use App\Services\PracticeService;
use App\Services\QuestionEditService;
use Database\Seeders\TierAndLevelSeeder;
use Database\Seeders\TosSeeder;

function openFullscreenPracticeFixture(): array
{
    test()->seed([TierAndLevelSeeder::class, TosSeeder::class]);
    $author = User::factory()->create(['role' => 'admin']);
    $competency = TosCompetency::query()->firstOrFail();
    foreach ([1, 2] as $number) {
        app(QuestionEditService::class)->createPractice($author, [
            'competency_id' => $competency->competency_id,
            'question_text' => 'Original fullscreen fixture question '.$number,
            'choices' => ['A' => ['text' => 'Answer Alpha'], 'B' => ['text' => 'Answer Bravo'], 'C' => ['text' => 'Answer Charlie'], 'D' => ['text' => 'Answer Delta']],
            'correct_choice' => 'A',
            'hypercorrection_rationale' => 'Alpha matches this original test concept.',
            'source_reference' => 'Original automated test fixture.',
            'copyright_permission' => 'permission confirmed',
        ]);
    }
    $student = User::factory()->create(['role' => 'student', 'is_diagnostic_completed' => true, 'total_xp' => 230, 'current_level' => 2]);
    foreach (TosCompetency::query()->get(['competency_id']) as $fixtureCompetency) {
        $student->knowledgeStates()->create([
            'competency_id' => $fixtureCompetency->competency_id,
            'current_mastery_p_l' => 0.45,
            'total_attempts' => 2,
            'total_correct' => 1,
            'last_evaluated_at' => now(),
        ]);
    }
    expect($student->knowledgeStates()->count())->toBe(TosCompetency::query()->count());
    $session = app(PracticeService::class)->startSession($student, 10);
    // Two answers exercise completion without making this browser regression a full exam.
    $session->forceFill(['target_length' => 2])->save();

    test()->actingAs($student);
    $page = visit(route('practice.show', $session, false))->assertSee('Before you answer');
    // Exercise the guard deterministically; real browser fullscreen still needs a manual check.
    $page->script(<<<'JS'
        (() => {
            window.__practiceFullscreen = null;
            window.__practiceDocument = document;
            Object.defineProperty(document, 'fullscreenEnabled', { configurable: true, get: () => true });
            Object.defineProperty(document, 'fullscreenElement', { configurable: true, get: () => window.__practiceFullscreen });
            document.documentElement.requestFullscreen = async () => {
                window.__practiceFullscreen = document.documentElement;
                document.dispatchEvent(new Event('fullscreenchange'));
            };
        })();
        JS);
    $page->click('[data-assessment-enter]');

    return [$page, $session];
}

it('keeps the same fullscreen document for answers, bookmarks and Continue, then leaves for the summary', function (): void {
    [$page, $session] = openFullscreenPracticeFixture();
    $page->assertSee('Question 1 of 2')->click('button[aria-label="Bookmark this question"]')->assertSee('Bookmark saved.')
        ->assertScript('document === window.__practiceDocument')
        ->assertScript('document.fullscreenElement === document.documentElement');
    $page->click('Answer Alpha')->click('Submit Answer')->assertSee('Clinical rationale')
        ->assertScript('document === window.__practiceDocument')
        ->assertScript('document.fullscreenElement === document.documentElement')
        ->click('button[aria-label="Remove bookmark"]')->assertSee('Bookmark removed.')->assertSee('Clinical rationale')
        ->click('Continue')->assertSee('Question 2 of 2')
        ->assertScript('document === window.__practiceDocument')
        ->assertScript('document.fullscreenElement === document.documentElement');

    $page->script(<<<'JS'
        window.__practiceFullscreen = null;
        document.dispatchEvent(new Event('fullscreenchange'));
        JS);
    $page->assertSee('Resume your assessment')->assertScript("document.querySelector('[data-assessment-content]').inert")
        ->click('[data-assessment-enter]')->assertScript("document.querySelector('[data-assessment-content]').inert", false);
    $page->click('Answer Alpha')->click('Submit Answer')
        ->assertPathIs('/practice/'.$session->session_id.'/summary')->assertSee('Level 3')->assertNoJavaScriptErrors();
    expect($session->fresh()->total_items)->toBe(2)->and($session->fresh()->completed_at)->not->toBeNull();
});

it('keeps server validation inline and requires reload after an uncertain network failure', function (): void {
    [$page, $session] = openFullscreenPracticeFixture();
    $page->script(<<<'JS'
        const radio = document.querySelector('[name="selected_choice_id"]');
        radio.value = 'not-a-uuid';
        radio.checked = true;
        JS);
    $page->click('Submit Answer')->assertSee('must be a valid UUID')
        ->assertScript('document === window.__practiceDocument')
        ->assertScript('document.fullscreenElement === document.documentElement');
    expect($session->fresh()->total_items)->toBe(0);

    $page->click('Answer Alpha');
    $page->script(<<<'JS'
        (() => {
            window.fetch = () => Promise.reject(new TypeError('offline'));
        })();
        JS);
    $page->click('Submit Answer')->assertSee('Connection lost.')
        ->assertDisabled('Submit Answer')->assertVisible('[data-practice-request-error]')
        ->assertScript('document === window.__practiceDocument');
    expect($session->fresh()->total_items)->toBe(0);
    $page->click('Reload practice')->assertSee('Before you answer')->assertVisible('[data-assessment-gate][open]');
});
