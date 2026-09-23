<?php

namespace App\Services;

use App\Models\Question;
use App\Models\TosDomain;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use RuntimeException;

class ResearchFormService
{
    public const SUBJECT_COUNT = 6;

    public const ITEMS_PER_SUBJECT = 10;

    public const TOTAL_ITEMS = 60;

    /**
     * @return Collection<int, Question>
     */
    public function load(
        string $form
    ): Collection {
        $this->assertKnownForm($form);

        $questions = $this->query($form)
            ->with([
                'choices' => fn ($query) => $query->orderBy(
                    'choice_letter'
                ),

                'competency.domain',
            ])
            ->orderBy('form_position')
            ->get();

        $this->assertBlueprint(
            $form,
            $questions,
        );

        return $questions;
    }

    /**
     * Uses two queries:
     *
     * 1. One grouped question count.
     * 2. One query for all subjects.
     */
    public function availability(
        string $form
    ): Collection {
        $this->assertKnownForm($form);

        $counts = $this->query($form)
            ->join(
                'tos_competencies',
                'tos_competencies.competency_id',
                '=',
                'questions.competency_id',
            )
            ->selectRaw(
                'tos_competencies.domain_id, '
                    .'COUNT(*) AS available'
            )
            ->groupBy(
                'tos_competencies.domain_id'
            )
            ->pluck(
                'available',
                'tos_competencies.domain_id',
            );

        return TosDomain::query()
            ->select([
                'domain_id',
                'domain_number',
                'domain_name',
            ])
            ->orderBy('domain_number')
            ->get()
            ->map(
                fn (TosDomain $domain) => [
                    'domain_id' => $domain->domain_id,

                    'domain_number' => $domain->domain_number,

                    'domain_name' => $domain->domain_name,

                    'available' => (int) (
                        $counts[
                            $domain->domain_id
                        ] ?? 0
                    ),

                    'required' => self::ITEMS_PER_SUBJECT,
                ]
            );
    }

    private function query(
        string $form
    ): Builder {
        return Question::query()
            ->where(
                'research_form',
                $form
            )
            ->where(
                'is_active',
                true
            )
            ->when(
                $this->requiresValidatedItems(),

                fn (Builder $query) => $query->whereNotNull(
                    'research_validated_at'
                )
            );
    }

    private function requiresValidatedItems(): bool
    {
        $localOverride =
            app()->environment(
                'local',
                'testing',
            )
            && config(
                'citirx.research.'
                    .'allow_unvalidated_forms',
                false,
            );

        return ! $localOverride;
    }

    private function assertKnownForm(
        string $form
    ): void {
        if (
            ! in_array(
                $form,
                [
                    Question::FORM_PRE_TEST_A,
                    Question::FORM_POST_TEST_B,
                ],
                true,
            )
        ) {
            throw new InvalidArgumentException(
                "Unknown research form: {$form}"
            );
        }
    }

    private function assertBlueprint(
        string $form,
        Collection $questions,
    ): void {
        if (
            $questions->count()
            !== self::TOTAL_ITEMS
        ) {
            throw new RuntimeException(
                "{$form} requires exactly 60 "
                    .'active and approved questions; '
                    ."found {$questions->count()}."
            );
        }

        $positions = $questions
            ->pluck('form_position')
            ->map(
                fn ($position) => (int) $position
            )
            ->sort()
            ->values()
            ->all();

        if (
            $positions
            !== range(
                1,
                self::TOTAL_ITEMS
            )
        ) {
            throw new RuntimeException(
                "{$form} positions must uniquely "
                    .'cover 1 through 60.'
            );
        }

        $perSubject = $questions->groupBy(
            fn (Question $question) => $question
                ->competency
                ->domain_id
        );

        if (
            $perSubject->count()
            !== self::SUBJECT_COUNT
        ) {
            throw new RuntimeException(
                "{$form} must cover all "
                    .'six subjects.'
            );
        }

        foreach (
            $perSubject as $subjectQuestions
        ) {
            if (
                $subjectQuestions->count()
                !== self::ITEMS_PER_SUBJECT
            ) {
                throw new RuntimeException(
                    "{$form} requires exactly "
                        .'10 items per subject.'
                );
            }
        }

        foreach ($questions as $question) {
            $correctChoices = $question
                ->choices
                ->where(
                    'is_correct',
                    true
                )
                ->count();

            if (
                $question->choices->count() !== 4
                || $correctChoices !== 1
            ) {
                throw new RuntimeException(
                    'Question '
                        ."{$question->question_id} "
                        .'must have four choices '
                        .'and exactly one correct '
                        .'answer.'
                );
            }
        }
    }
}
