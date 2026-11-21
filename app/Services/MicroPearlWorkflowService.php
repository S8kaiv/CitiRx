<?php

namespace App\Services;

use App\Models\MicroPearl;
use App\Models\MicroPearlEdit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use LogicException;

class MicroPearlWorkflowService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(
        User $editor,
        array $data,
    ): MicroPearl {
        $this->assertEditor($editor);

        return DB::transaction(function () use (
            $editor,
            $data,
        ): MicroPearl {
            $content = $this->normalizeContent($data);

            $pearl = new MicroPearl;

            $pearl->forceFill(array_merge(
                $content,
                [
                    'created_by' => $editor->user_id,
                    'slug' => Str::slug($data['title'])
                        .'-'
                        .Str::lower(Str::random(8)),
                    'content_status' => MicroPearl::STATUS_DRAFT,
                    'content_version' => 1,
                    'edit_version' => 1,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'submitted_at' => null,
                    'published_at' => null,
                    'retired_at' => null,
                ],
            ));

            $pearl->save();

            $this->recordEdit(
                $pearl,
                $editor,
                'created',
                [
                    'created' => [
                        'old' => null,
                        'new' => true,
                    ],
                ],
                null,
                0,
                1,
            );

            return $pearl->fresh([
                'competency.domain',
                'creator',
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(
        User $editor,
        MicroPearl $pearl,
        array $data,
        int $expectedVersion,
        ?string $reason,
    ): MicroPearl {
        $this->assertEditor($editor);

        return DB::transaction(function () use (
            $editor,
            $pearl,
            $data,
            $expectedVersion,
            $reason,
        ): MicroPearl {
            $pearl = $this->lock($pearl);

            if ($editor->role === 'faculty' && $pearl->created_by !== $editor->user_id) {
                abort(403, 'Only the author may edit a faculty draft.');
            }

            $this->assertVersion(
                $pearl,
                $expectedVersion,
            );

            if (
                $pearl->content_status
                !== MicroPearl::STATUS_DRAFT
            ) {
                throw new LogicException(
                    'Only draft Micro Pearls may be edited.',
                );
            }

            $content = $this->normalizeContent($data);
            $changes = $this->changes($pearl, $content);

            if ($changes === []) {
                return $pearl;
            }

            $before = (int) $pearl->edit_version;
            $after = $before + 1;

            $pearl->forceFill(array_merge(
                $content,
                [
                    'content_version' => (
                        (int) $pearl->content_version
                    ) + 1,
                    'edit_version' => $after,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'published_at' => null,
                ],
            ))->save();

            $this->recordEdit(
                $pearl,
                $editor,
                'content_updated',
                $changes,
                $reason,
                $before,
                $after,
            );

            return $pearl->fresh([
                'competency.domain',
                'creator',
            ]);
        });
    }

    public function submitForReview(
        User $editor,
        MicroPearl $pearl,
        int $expectedVersion,
        ?string $reason,
    ): MicroPearl {
        $this->assertEditor($editor);

        return DB::transaction(function () use (
            $editor,
            $pearl,
            $expectedVersion,
            $reason,
        ): MicroPearl {
            $pearl = $this->lock($pearl);

            if ($editor->role === 'faculty' && $pearl->created_by !== $editor->user_id) {
                abort(403, 'Only the author may submit this faculty draft.');
            }

            $this->assertVersion(
                $pearl,
                $expectedVersion,
            );

            if (
                $pearl->content_status
                !== MicroPearl::STATUS_DRAFT
            ) {
                throw new LogicException(
                    'Only a draft can be submitted for review.',
                );
            }

            $this->assertPublishableContent($pearl);

            return $this->transition(
                $pearl,
                $editor,
                MicroPearl::STATUS_IN_REVIEW,
                'submitted_for_review',
                $reason,
                [
                    'submitted_at' => now(),
                ],
            );
        });
    }

    public function returnToDraft(
        User $editor,
        MicroPearl $pearl,
        int $expectedVersion,
        string $reason,
    ): MicroPearl {
        $this->assertEditor($editor);

        return DB::transaction(function () use (
            $editor,
            $pearl,
            $expectedVersion,
            $reason,
        ): MicroPearl {
            $pearl = $this->lock($pearl);

            $this->assertVersion(
                $pearl,
                $expectedVersion,
            );

            if (
                $pearl->content_status
                !== MicroPearl::STATUS_IN_REVIEW
            ) {
                throw new LogicException(
                    'Only a pearl awaiting review can be returned.',
                );
            }

            return $this->transition(
                $pearl,
                $editor,
                MicroPearl::STATUS_DRAFT,
                'returned_for_revision',
                $reason,
                [
                    'submitted_at' => null,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'published_at' => null,
                ],
            );
        });
    }

    public function publish(
        User $reviewer,
        MicroPearl $pearl,
        int $expectedVersion,
        string $reason,
        ?string $publishedAt,
    ): MicroPearl {
        $this->assertEditor($reviewer);

        return DB::transaction(function () use (
            $reviewer,
            $pearl,
            $expectedVersion,
            $reason,
            $publishedAt,
        ): MicroPearl {
            $pearl = $this->lock($pearl);

            $this->assertVersion(
                $pearl,
                $expectedVersion,
            );

            $isAdministrator = in_array($reviewer->role, ['admin', 'dean'], true);
            $isAuthor = $pearl->created_by !== null
                && $pearl->created_by === $reviewer->user_id;

            if (
                $pearl->content_status !== MicroPearl::STATUS_IN_REVIEW
                && ! (
                    $pearl->content_status === MicroPearl::STATUS_DRAFT
                    && $isAdministrator
                    && $isAuthor
                )
            ) {
                throw new LogicException(
                    'Only a Pearl awaiting review or an administrator-authored draft can be published.',
                );
            }

            if (
                $isAuthor
                && ! $isAdministrator
            ) {
                throw new LogicException(
                    'The author cannot approve and publish their own Micro Pearl.',
                );
            }

            $this->assertPublishableContent($pearl);

            return $this->transition(
                $pearl,
                $reviewer,
                MicroPearl::STATUS_PUBLISHED,
                $isAdministrator && $isAuthor
                    ? 'admin_published'
                    : 'published',
                $reason,
                [
                    'reviewed_by' => $reviewer->user_id,
                    'reviewed_at' => now(),
                    'published_at' => filled($publishedAt)
                        ? CarbonImmutable::parse($publishedAt)
                        : now(),
                    'retired_at' => null,
                ],
            );
        });
    }

    public function retire(
        User $editor,
        MicroPearl $pearl,
        int $expectedVersion,
        string $reason,
    ): MicroPearl {
        $this->assertEditor($editor);

        return DB::transaction(function () use (
            $editor,
            $pearl,
            $expectedVersion,
            $reason,
        ): MicroPearl {
            $pearl = $this->lock($pearl);

            $this->assertVersion(
                $pearl,
                $expectedVersion,
            );

            if (
                $pearl->content_status
                !== MicroPearl::STATUS_PUBLISHED
            ) {
                throw new LogicException(
                    'Only a published Micro Pearl can be retired.',
                );
            }

            return $this->transition(
                $pearl,
                $editor,
                MicroPearl::STATUS_RETIRED,
                'retired',
                $reason,
                [
                    'retired_at' => now(),
                ],
            );
        });
    }

    private function lock(
        MicroPearl $pearl,
    ): MicroPearl {
        return MicroPearl::query()
            ->lockForUpdate()
            ->findOrFail($pearl->pearl_id);
    }

    private function assertEditor(
        User $editor,
    ): void {
        if (
            ! in_array(
                $editor->role,
                ['admin', 'dean', 'faculty'],
                true,
            )
        ) {
            throw new LogicException(
                'Only administrators, deans, and faculty may manage Micro Pearls.',
            );
        }
    }

    private function assertVersion(
        MicroPearl $pearl,
        int $expectedVersion,
    ): void {
        if (
            (int) $pearl->edit_version
            !== $expectedVersion
        ) {
            throw new LogicException(
                'This Micro Pearl was changed by another user. Reload the page.',
            );
        }
    }

    private function assertPublishableContent(
        MicroPearl $pearl,
    ): void {
        $errors = [];
        foreach ([
            'title' => $pearl->title, 'mnemonic_summary' => $pearl->mnemonic_summary,
            'lesson_content' => $pearl->lesson_content, 'learning_objectives_text' => $pearl->learning_objectives,
            'key_points_text' => $pearl->key_points, 'recall_prompts' => $pearl->recall_prompts,
            'source_references_text' => $pearl->source_references,
        ] as $field => $value) {
            if (blank($value)) {
                $errors[$field] = 'Complete this section before submitting for review or publication.';
            }
        }
        foreach ($pearl->recall_prompts ?? [] as $index => $prompt) {
            if (blank($prompt['question'] ?? null)) {
                $errors["recall_prompts.{$index}.question"] = 'Enter a recall question.';
            }
            if (blank($prompt['answer'] ?? null)) {
                $errors["recall_prompts.{$index}.answer"] = 'Enter its answer.';
            }
        }

        if (
            ! in_array(
                $pearl->rights_status,
                MicroPearl::PUBLISHABLE_RIGHTS_STATUSES,
                true,
            )
        ) {
            $errors['rights_status'] = 'Resolve source rights before review or publication.';
        }

        if (
            $pearl->is_ai_generated
            && blank($pearl->generation_metadata)
        ) {
            $errors['lesson_content'] = 'Legacy AI-assisted content requires generation metadata.';
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeContent(
        array $data,
    ): array {
        return [
            'competency_id' => $data['competency_id'],
            'title' => trim($data['title']),
            'mnemonic_summary' => $this->nullable(
                $data['mnemonic_summary'] ?? null,
            ),
            'lesson_content' => $this->nullable(
                $data['lesson_content'] ?? null,
            ),
            'learning_objectives' => $this->lines(
                $data['learning_objectives_text'] ?? null,
            ),
            'key_points' => $this->lines(
                $data['key_points_text'] ?? null,
            ),
            'common_pitfalls' => $this->lines(
                $data['common_pitfalls_text'] ?? null,
            ),
            'recall_prompts' => array_key_exists('recall_prompts', $data)
                ? collect($data['recall_prompts'] ?? [])->map(fn ($p) => ['question' => trim((string) ($p['question'] ?? '')), 'answer' => trim((string) ($p['answer'] ?? ''))])
                    ->filter(fn ($p) => $p['question'] !== '' || $p['answer'] !== '')->values()->all()
                : $this->recallPrompts($data['recall_prompts_text'] ?? null),
            'source_references' => $this->lines(
                $data['source_references_text'] ?? null,
            ),
            'estimated_read_seconds' => (int) ($data['estimated_read_seconds'] ?? 180),
            'order_index' => (int) ($data['order_index'] ?? (1 + MicroPearl::query()->where('competency_id', $data['competency_id'])->max('order_index'))),
            'rights_status' => $data['rights_status'] ?? MicroPearl::RIGHTS_PENDING,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function lines(
        mixed $value,
    ): array {
        if (blank($value)) {
            return [];
        }

        return collect(
            preg_split(
                '/\R/u',
                (string) $value,
            ) ?: [],
        )
            ->map(
                fn (string $line): string => trim($line),
            )
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * One prompt per line:
     * Question | Answer
     *
     * @return array<int, array{question: string, answer: string}>
     */
    private function recallPrompts(
        mixed $value,
    ): array {
        $prompts = [];

        foreach ($this->lines($value) as $line) {
            $parts = array_map(
                'trim',
                explode('|', $line, 2),
            );

            if (
                count($parts) !== 2
                || $parts[0] === ''
                || $parts[1] === ''
            ) {
                throw new InvalidArgumentException(
                    'Every recall prompt must use: Question | Answer',
                );
            }

            $prompts[] = [
                'question' => $parts[0],
                'answer' => $parts[1],
            ];
        }

        return $prompts;
    }

    private function nullable(
        mixed $value,
    ): ?string {
        $value = trim((string) $value);

        return $value === ''
            ? null
            : $value;
    }

    /**
     * @param  array<string, mixed>  $new
     * @return array<string, array{old: mixed, new: mixed}>
     */
    private function changes(
        MicroPearl $pearl,
        array $new,
    ): array {
        $changes = [];

        foreach ($new as $field => $newValue) {
            $oldValue = $pearl->{$field};

            if (
                json_encode($oldValue)
                === json_encode($newValue)
            ) {
                continue;
            }

            $changes[$field] = [
                'old' => $oldValue,
                'new' => $newValue,
            ];
        }

        return $changes;
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function transition(
        MicroPearl $pearl,
        User $editor,
        string $status,
        string $action,
        ?string $reason,
        array $extra,
    ): MicroPearl {
        $before = (int) $pearl->edit_version;
        $after = $before + 1;
        $oldStatus = $pearl->content_status;

        $pearl->forceFill(array_merge(
            [
                'content_status' => $status,
                'edit_version' => $after,
            ],
            $extra,
        ))->save();

        $this->recordEdit(
            $pearl,
            $editor,
            $action,
            [
                'content_status' => [
                    'old' => $oldStatus,
                    'new' => $status,
                ],
            ],
            $reason,
            $before,
            $after,
        );

        return $pearl->fresh([
            'competency.domain',
            'creator',
            'reviewer',
        ]);
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function recordEdit(
        MicroPearl $pearl,
        User $editor,
        string $action,
        array $changes,
        ?string $reason,
        int $before,
        int $after,
    ): void {
        MicroPearlEdit::query()->create([
            'pearl_id' => $pearl->pearl_id,
            'editor_id' => $editor->user_id,
            'action' => $action,
            'changes' => $changes ?: null,
            'reason' => filled($reason) ? trim((string) $reason) : null,
            'version_before' => $before,
            'version_after' => $after,
            'edited_at' => now(),
        ]);
    }
}
