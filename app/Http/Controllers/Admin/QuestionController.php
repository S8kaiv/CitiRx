<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\QuestionIndexRequest;
use App\Http\Requests\Admin\UpdateQuestionRequest;
use App\Http\Requests\Admin\UpdateQuestionStatusRequest;
use App\Models\Question;
use App\Models\TosDomain;
use App\Services\QuestionEditService;
use App\Services\QuestionLockService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use LogicException;

class QuestionController extends Controller
{
    public function __construct(
        protected QuestionLockService $lock,
        protected QuestionEditService $edits,
    ) {}

    public function index(
        QuestionIndexRequest $request,
    ): View {
        $filters = array_merge(
            [
                'form' => null,
                'domain_id' => null,
                'status' => null,
                'q' => null,
            ],
            $request->validated(),
        );

        $query = Question::query()
            ->select([
                'question_id',
                'competency_id',
                'question_text',
                'research_form',
                'form_position',
                'research_validated_at',
                'research_validated_by',
                'first_served_at',
                'is_active',
                'created_at',
            ])
            ->with([
                'competency' => fn ($query) => $query
                    ->select([
                        'competency_id',
                        'domain_id',
                        'competency_code',
                        'title',
                    ]),

                'competency.domain' => fn ($query) => $query
                    ->select([
                        'domain_id',
                        'domain_number',
                        'domain_name',
                    ]),
            ]);
        if ($filters['form'] !== null) {
            $query->where(
                'research_form',
                $filters['form'],
            );
        }

        if ($filters['domain_id'] !== null) {
            $query->whereHas(
                'competency',
                fn (Builder $query) => $query
                    ->where(
                        'domain_id',
                        $filters['domain_id'],
                    ),
            );
        }

        $this->applyStatusFilter(
            $query,
            $filters['status'],
        );

        if (filled($filters['q'])) {
            /*
            * Treat %, _, and backslashes as literal search characters
            * instead of allowing them to act as LIKE wildcards.
            */
            $search = addcslashes(
                $filters['q'],
                '\\%_',
            );

            $query->where(
                'question_text',
                'like',
                "%{$search}%",
            );
        }

        $questions = $query
            ->orderByRaw(
                'research_form IS NULL',
            )
            ->orderBy(
                'research_form',
            )
            ->orderBy(
                'form_position',
            )
            ->orderByDesc(
                'created_at',
            )
            ->paginate(30)
            ->withQueryString();

        return view(
            'admin.questions.index',
            [
                'questions' => $questions,

                'domains' => TosDomain::query()
                    ->orderBy(
                        'domain_number',
                    )
                    ->get(),

                'filters' => $filters,
            ],
        );
    }

    public function show(
        Request $request,
        Question $question,
    ): View {
        $question->load([
            'choices' => fn ($query) => $query
                ->orderBy(
                    'choice_letter',
                ),

            'competency.domain',

            'researchValidator',
        ]);

        /*
         * Keep audit history bounded instead of eagerly
         * loading every edit ever made.
         */
        $history = $question->edits()
            ->with(
                'editor',
            )
            ->paginate(20)
            ->withQueryString();

        return view(
            'admin.questions.show',
            [
                'question' => $question,

                'history' => $history,

                'isLocked' => $this->lock->isLocked(
                    $question,
                ),

                'lockReason' => $this->lock->lockReason(
                    $question,
                ),

                'canReactivate' => $this->lock->canReactivate(
                    $question,
                ),
            ],
        );
    }

    public function edit(
        Request $request,
        Question $question,
    ): View|RedirectResponse {
        if (
            $this->lock->isLocked(
                $question,
            )
        ) {
            return redirect()
                ->route(
                    'admin.questions.show',
                    [
                        'question' => $question->question_id,
                    ],
                )
                ->with(
                    'error',
                    'This question is locked: '
                        .$this->lock->lockReason(
                            $question,
                        ),
                );
        }

        $question->load([
            'choices' => fn ($query) => $query
                ->orderBy(
                    'choice_letter',
                ),

            'competency.domain',
        ]);

        return view(
            'admin.questions.edit',
            [
                'question' => $question,
            ],
        );
    }

    public function update(
        UpdateQuestionRequest $request,
        Question $question,
    ): RedirectResponse {
        $validated =
            $request->validated();

        try {
            $this->edits->updateContent(
                editor: $request->user(),
                question: $question,
                payload: $validated,
                expectedVersion: (int)
                $validated['edit_version'],
                reason: $validated['change_reason'],
            );
        } catch (
            LogicException
            |InvalidArgumentException
            $exception
        ) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    $exception->getMessage(),
                );
        }

        return redirect()
            ->route(
                'admin.questions.show',
                [
                    'question' => $question->question_id,
                ],
            )
            ->with(
                'status',
                'Question updated and audit history recorded.',
            );
    }

    public function updateStatus(
        UpdateQuestionStatusRequest $request,
        Question $question,
    ): RedirectResponse {
        $validated =
            $request->validated();

        try {
            $updated =
                $this->edits->setActive(
                    editor: $request->user(),
                    question: $question,
                    active: $request->boolean(
                        'active',
                    ),
                    expectedVersion: (int)
                    $validated['edit_version'],
                    reason: $validated['change_reason'],
                );
        } catch (
            LogicException
            |InvalidArgumentException
            $exception
        ) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    $exception->getMessage(),
                );
        }

        return redirect()
            ->route(
                'admin.questions.show',
                [
                    'question' => $question->question_id,
                ],
            )
            ->with(
                'status',
                $updated->is_active
                    ? 'Question reactivated.'
                    : 'Question deactivated. It will not be used in future sessions.',
            );
    }

    private function applyStatusFilter(
        Builder $query,
        ?string $status,
    ): void {
        match ($status) {
            'validated' => $query
                ->whereNotNull(
                    'research_validated_at',
                )
                ->whereNotNull(
                    'research_validated_by',
                ),

            'unvalidated' => $query
                ->whereNull(
                    'research_validated_at',
                )
                ->whereNull(
                    'research_validated_by',
                ),

            'validation_issue' => $query
                ->where(
                    function (
                        Builder $query,
                    ): void {
                        $query
                            ->where(
                                function (
                                    Builder $query,
                                ): void {
                                    $query
                                        ->whereNotNull(
                                            'research_validated_at',
                                        )
                                        ->whereNull(
                                            'research_validated_by',
                                        );
                                },
                            )
                            ->orWhere(
                                function (
                                    Builder $query,
                                ): void {
                                    $query
                                        ->whereNull(
                                            'research_validated_at',
                                        )
                                        ->whereNotNull(
                                            'research_validated_by',
                                        );
                                },
                            );
                    },
                ),

            'served' => $query
                ->whereNotNull(
                    'first_served_at',
                ),

            'unserved' => $query
                ->whereNull(
                    'first_served_at',
                ),

            'locked' => $query
                ->where(
                    function (
                        Builder $query,
                    ): void {
                        $query
                            ->whereNotNull(
                                'first_served_at',
                            )
                            ->orWhereNotNull(
                                'research_validated_at',
                            )
                            ->orWhereNotNull(
                                'research_validated_by',
                            );
                    },
                ),

            'editable' => $query
                ->whereNull(
                    'first_served_at',
                )
                ->whereNull(
                    'research_validated_at',
                )
                ->whereNull(
                    'research_validated_by',
                ),

            'active' => $query
                ->where(
                    'is_active',
                    true,
                ),

            'inactive' => $query
                ->where(
                    'is_active',
                    false,
                ),

            'placeholder' => $query
                ->where(
                    'question_text',
                    'like',
                    '[DEVELOPMENT MOCK - NOT VALIDATED]%',
                ),

            default => null,
        };
    }
}
