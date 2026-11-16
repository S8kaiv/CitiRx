<?php

use App\Models\Question;
use App\Services\ResearchFormBlueprint;

it('keeps Form A at 60 questions and allocates 100 Form B questions by TOS domain', function (): void {
    expect(ResearchFormBlueprint::total(Question::FORM_PRE_TEST_A))->toBe(60)
        ->and(ResearchFormBlueprint::total(Question::FORM_POST_TEST_B))->toBe(100);

    $expectedDomainTotals = [20, 15, 18, 15, 17, 15];

    foreach ($expectedDomainTotals as $index => $total) {
        expect(ResearchFormBlueprint::domainTotal(
            Question::FORM_POST_TEST_B,
            $index + 1,
        ))->toBe($total);
    }

    foreach (ResearchFormBlueprint::quotas(Question::FORM_POST_TEST_B) as $domain => $competencies) {
        expect(array_sum($competencies))->toBe(
            ResearchFormBlueprint::domainTotal(Question::FORM_POST_TEST_B, $domain),
        );
    }
});
