<?php

namespace App\Services;

use App\Models\Question;
use App\Models\ResearchFormVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class DeanResearchFormReviewService
{
    public function __construct(
        protected ResearchFormAuditService $formAudit,
        protected PilotControlService $pilotControls,
        protected AdminAuditService $adminAudit,
    ) {}

    public function questions(ResearchFormVersion $version): Collection
    {
        return Question::query()
            ->where('form_version_id', $version->form_version_id)
            ->where('research_form', $version->research_form)
            ->where('is_active', true)
            ->with([
                'choices' => fn ($query) => $query->orderBy('choice_letter'),
                'competency.domain',
            ])
            ->orderBy('form_position')
            ->get();
    }

    public function digest(Collection $questions): string
    {
        $snapshot = $questions->map(fn (Question $question): array => [
            'id' => $question->question_id,
            'position' => (int) $question->form_position,
            'edit_version' => (int) $question->edit_version,
            'competency_id' => $question->competency_id,
            'question' => $question->question_text,
            'rationale' => $question->hypercorrection_rationale,
            'cognitive_level' => $question->cognitive_level,
            'source' => $question->source_reference,
            'rights' => $question->copyright_permission,
            'choices' => $question->choices->map(fn ($choice): array => [
                $choice->choice_letter,
                $choice->choice_text,
                (bool) $choice->is_correct,
                $choice->distractor_fallacy_note,
            ])->all(),
        ])->all();

        return hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR));
    }

    public function approveAll(
        User $dean,
        ResearchFormVersion $version,
        int $expectedLockVersion,
        string $expectedDigest,
    ): void {
        $this->assertDean($dean);

        DB::transaction(function () use ($dean, $version, $expectedLockVersion, $expectedDigest): void {
            $this->pilotControls->assertResearchConfigurationMutable();
            $version = ResearchFormVersion::query()
                ->whereKey($version->form_version_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertReviewable($version, $expectedLockVersion);

            $questions = Question::query()
                ->where('form_version_id', $version->form_version_id)
                ->where('research_form', $version->research_form)
                ->where('is_active', true)
                ->with([
                    'choices' => fn ($query) => $query->orderBy('choice_letter'),
                    'competency.domain',
                ])
                ->orderBy('form_position')
                ->lockForUpdate()
                ->get();

            if (! hash_equals($expectedDigest, $this->digest($questions))) {
                throw ValidationException::withMessages([
                    'review' => 'The questions changed since you opened this page. Review them again.',
                ]);
            }

            $report = $this->formAudit->auditVersion($version, requireValidation: false);

            if ($report['errors'] !== []) {
                throw ValidationException::withMessages(['review' => $report['errors']]);
            }

            Question::query()
                ->whereIn('question_id', $questions->modelKeys())
                ->update([
                    'research_validated_at' => now(),
                    'research_validated_by' => $dean->user_id,
                ]);

            $oldLockVersion = (int) $version->lock_version;
            $version->forceFill([
                'status' => ResearchFormVersion::STATUS_VALIDATED,
                'lock_version' => $oldLockVersion + 1,
                'dean_approved_by' => $dean->user_id,
                'dean_approved_at' => now(),
                'approved_content_digest' => $expectedDigest,
            ])->save();

            $this->adminAudit->record(
                $dean,
                'research_form.dean_approved',
                $version,
                [
                    'approved_questions' => ['old' => 0, 'new' => $questions->count()],
                    'content_sha256' => ['old' => null, 'new' => $expectedDigest],
                    'lock_version' => ['old' => $oldLockVersion, 'new' => $oldLockVersion + 1],
                ],
                'The Dean approved the complete '.$version->research_form.' version, its answers, blueprint, sources, and rights.',
            );
        }, attempts: 3);
    }

    public function returnForRevision(
        User $dean,
        ResearchFormVersion $version,
        int $expectedLockVersion,
        string $reason,
    ): void {
        $this->assertDean($dean);

        DB::transaction(function () use ($dean, $version, $expectedLockVersion, $reason): void {
            $this->pilotControls->assertResearchConfigurationMutable();
            $version = ResearchFormVersion::query()
                ->whereKey($version->form_version_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertReviewable($version, $expectedLockVersion);

            $version->questions()->update(['research_validated_at' => null, 'research_validated_by' => null]);
            $oldLockVersion = (int) $version->lock_version;
            $version->forceFill([
                'status' => ResearchFormVersion::STATUS_DRAFT,
                'dean_approved_by' => null,
                'dean_approved_at' => null,
                'approved_content_digest' => null,
                'lock_version' => $oldLockVersion + 1,
            ])->save();

            $this->adminAudit->record(
                $dean,
                'research_form.dean_returned',
                $version,
                [
                    'status' => ['old' => ResearchFormVersion::STATUS_IN_REVIEW, 'new' => ResearchFormVersion::STATUS_DRAFT],
                    'lock_version' => ['old' => $oldLockVersion, 'new' => $oldLockVersion + 1],
                ],
                $reason,
            );
        }, attempts: 3);
    }

    private function assertDean(User $user): void
    {
        abort_unless($user->role === 'dean', 403);
    }

    private function assertReviewable(ResearchFormVersion $version, int $expectedLockVersion): void
    {
        if (! in_array($version->research_form, [Question::FORM_PRE_TEST_A, Question::FORM_POST_TEST_B], true)
            || $version->status !== ResearchFormVersion::STATUS_IN_REVIEW
            || $version->first_served_at !== null
            || $version->questions()->whereNotNull('first_served_at')->exists()
            || $version->sessions()->exists()) {
            throw new LogicException('Only an unserved Form A or B version in review can be approved.');
        }

        if ((int) $version->lock_version !== $expectedLockVersion) {
            throw new LogicException('This form changed. Reload the review page.');
        }
    }
}
