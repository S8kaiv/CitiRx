<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionChoice;
use App\Models\ResearchFormVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResearchFormEditorService
{
    public function __construct(protected PilotControlService $pilot, protected AdminAuditService $audit) {}

    public function createVersion(User $actor, string $form): ResearchFormVersion
    {
        abort_unless(in_array($actor->role, ['admin', 'dean'], true), 403);

        return DB::transaction(function () use ($actor, $form) {
            $this->pilot->assertResearchConfigurationMutable();
            $number = ((int) ResearchFormVersion::query()->where('research_form', $form)->max('version_number')) + 1;
            $version = ResearchFormVersion::query()->create([
                'research_form' => $form, 'version_number' => $number, 'version_code' => $form.'_v'.$number,
                'status' => 'draft', 'is_active' => false, 'created_by' => $actor->user_id, 'lock_version' => 1,
            ]);
            $this->audit->record($actor, 'research_form_version.created', $version);

            return $version;
        });
    }

    public function save(User $actor, ResearchFormVersion $version, array $data): Question
    {
        abort_unless(in_array($actor->role, ['admin', 'dean'], true), 403);

        return DB::transaction(function () use ($actor, $version, $data) {
            $this->pilot->assertResearchConfigurationMutable();
            $version = ResearchFormVersion::query()->lockForUpdate()->findOrFail($version->form_version_id);
            $this->assertEditable($version, (int) $data['expected_lock_version']);
            $this->invalidateApproval($version);
            $question = Question::query()->where('form_version_id', $version->form_version_id)
                ->where('form_position', $data['form_position'])->lockForUpdate()->first();
            if ($question?->first_served_at !== null) {
                throw ValidationException::withMessages(['form_position' => 'This item has been served and cannot be changed.']);
            }
            $before = $question?->getAttributes();
            $beforeChoices = $question?->choices()->orderBy('choice_letter')->get(['choice_letter', 'choice_text', 'is_correct'])->toArray();
            $question ??= new Question;
            $question->forceFill([
                'form_version_id' => $version->form_version_id, 'research_form' => $version->research_form,
                'form_position' => $data['form_position'], 'competency_id' => $data['competency_id'],
                'question_text' => $data['question_text'], 'hypercorrection_rationale' => $data['hypercorrection_rationale'] ?? null,
                'cognitive_level' => $data['cognitive_level'], 'source_reference' => $data['source_reference'] ?? null,
                'copyright_permission' => $data['copyright_permission'] ?? null,
                'is_active' => true, 'is_diagnostic_pool' => $version->research_form === Question::FORM_PRE_TEST_A,
                'edit_version' => ((int) $question->edit_version) + 1,
            ])->save();
            foreach (['A', 'B', 'C', 'D'] as $letter) {
                QuestionChoice::query()->updateOrCreate(['question_id' => $question->question_id, 'choice_letter' => $letter], [
                    'choice_text' => $data['choices'][$letter], 'is_correct' => $data['correct_choice'] === $letter,
                ]);
            }
            $version->forceFill(['lock_version' => $version->lock_version + 1])->save();
            $this->audit->record($actor, 'research_form.question_saved', $question, [
                'question' => ['old' => $before, 'new' => $question->getAttributes()],
                'choices' => ['old' => $beforeChoices, 'new' => $question->choices()->orderBy('choice_letter')->get(['choice_letter', 'choice_text', 'is_correct'])->toArray()],
            ], 'Edited in the app. The complete version requires fresh Dean approval.');

            return $question;
        });
    }

    public function assertEditable(ResearchFormVersion $version, int $expectedLock): void
    {
        if ($version->first_served_at !== null || $version->sessions()->exists() || $version->questions()->whereNotNull('first_served_at')->exists()
            || in_array($version->status, ['published', 'retired'], true)) {
            throw ValidationException::withMessages(['version' => 'Published or served versions are immutable. Copy this version to a new draft first.']);
        }
        if ($version->lock_version !== $expectedLock) {
            throw ValidationException::withMessages(['expected_lock_version' => 'The form changed in another window. Reload before saving.']);
        }
    }

    public function invalidateApproval(ResearchFormVersion $version): void
    {
        // Clear approval before editing any unserved content, including its choices.
        $version->questions()->update(['research_validated_at' => null, 'research_validated_by' => null]);
        $version->forceFill(['status' => 'draft', 'dean_approved_by' => null,
            'dean_approved_at' => null, 'approved_content_digest' => null])->save();
    }
}
