<?php

use App\Models\ResearchFormVersion;
use App\Models\User;
use App\Services\DeanResearchFormReviewService;
use App\Services\ResearchFormVersionManagementService;
use Illuminate\Support\Facades\DB;

function makeResearchFormFixtureReviewable(ResearchFormVersion $version): void
{
    // Test content only: never remove production locks to make a fixture pass.
    $hasProtectedQuestions = $version->questions()->where(function ($query): void {
        $query->whereNotNull('first_served_at')->orWhereNotNull('research_validated_at')->orWhereNotNull('research_validated_by');
    })->exists();
    if ($version->status !== ResearchFormVersion::STATUS_DRAFT || $version->first_served_at !== null || $version->sessions()->exists() || $hasProtectedQuestions) {
        throw new LogicException('Prepare an unserved draft fixture before Dean review.');
    }

    DB::table('questions')->where('form_version_id', $version->form_version_id)->update([
        'question_text' => DB::raw("REPLACE(question_text, '[DEVELOPMENT MOCK - NOT VALIDATED] ', '')"),
        'hypercorrection_rationale' => 'A test-authored explanation for the correct answer.',
        'source_reference' => 'Original content authored solely for this automated test.',
        'copyright_permission' => 'permission confirmed',
    ]);
    DB::table('question_choices')->whereIn('question_id', $version->questions()->select('question_id'))->update([
        'choice_text' => DB::raw("CONCAT('Answer ', choice_letter)"),
        'distractor_fallacy_note' => null,
    ]);
}

function approveResearchFormFixture(ResearchFormVersion $version, User $dean): ResearchFormVersion
{
    $version->refresh();
    if ($version->status === ResearchFormVersion::STATUS_DRAFT) {
        $version = app(ResearchFormVersionManagementService::class)->submitForReview(
            $dean, $version, $version->lock_version, 'Submit the complete test fixture for Dean review.',
        );
    }
    $reviews = app(DeanResearchFormReviewService::class);
    $reviews->approveAll($dean, $version, $version->lock_version, $reviews->digest($reviews->questions($version)));

    return $version->fresh();
}
