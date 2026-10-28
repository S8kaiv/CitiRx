<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\MicroPearlTransitionRequest;
use App\Http\Requests\Content\PublishMicroPearlRequest;
use App\Http\Requests\Content\SaveMicroPearlRequest;
use App\Models\MicroPearl;
use App\Models\TosDomain;
use App\Services\MicroPearlWorkflowService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use LogicException;

class MicroPearlManagementController extends Controller
{
    public function __construct(
        protected MicroPearlWorkflowService $workflow,
    ) {}

    public function index(
        Request $request,
    ): View {
        $status = $request->string('status')
            ->trim()
            ->value();

        $search = $request->string('q')
            ->trim()
            ->value();

        $pearls = MicroPearl::query()
            ->with([
                'competency.domain',
                'creator',
                'reviewer',
            ])
            ->when(
                in_array(
                    $status,
                    [
                        MicroPearl::STATUS_DRAFT,
                        MicroPearl::STATUS_IN_REVIEW,
                        MicroPearl::STATUS_PUBLISHED,
                        MicroPearl::STATUS_RETIRED,
                    ],
                    true,
                ),
                fn (Builder $query) => $query->where(
                    'content_status',
                    $status,
                ),
            )
            ->when(
                $search !== '',
                function (
                    Builder $query,
                ) use ($search): void {
                    $escaped = addcslashes(
                        $search,
                        '\\%_',
                    );

                    $query->where(
                        function (
                            Builder $searchQuery,
                        ) use ($escaped): void {
                            $searchQuery
                                ->where(
                                    'title',
                                    'like',
                                    "%{$escaped}%",
                                )
                                ->orWhere(
                                    'mnemonic_summary',
                                    'like',
                                    "%{$escaped}%",
                                );
                        },
                    );
                },
            )
            ->latest('updated_at')
            ->paginate(25)
            ->withQueryString();

        return view(
            'content.pearls.index',
            compact(
                'pearls',
                'status',
                'search',
            ),
        );
    }

    public function create(): View
    {
        return view('content.pearls.create', [
            'domains' => $this->domains(),
        ]);
    }

    public function store(
        SaveMicroPearlRequest $request,
    ): RedirectResponse {
        try {
            $pearl = $this->workflow->create(
                $request->user(),
                $request->validated(),
            );
        } catch (
            InvalidArgumentException
            |LogicException
            $exception
        ) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route(
                'content.pearls.edit',
                ['pearl' => $pearl->pearl_id],
            )
            ->with('status', 'Micro Pearl draft created.');
    }

    public function edit(
        MicroPearl $pearl,
    ): View {
        $pearl->load([
            'competency.domain',
            'creator',
            'reviewer',
            'edits.editor',
        ]);

        return view('content.pearls.edit', [
            'pearl' => $pearl,
            'domains' => $this->domains(),
        ]);
    }

    public function update(
        SaveMicroPearlRequest $request,
        MicroPearl $pearl,
    ): RedirectResponse {
        try {
            $this->workflow->update(
                $request->user(),
                $pearl,
                $request->validated(),
                $request->integer('edit_version'),
                $request->validated('reason'),
            );
        } catch (
            InvalidArgumentException
            |LogicException
            $exception
        ) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return back()->with(
            'status',
            'Micro Pearl updated.',
        );
    }

    public function submitReview(
        MicroPearlTransitionRequest $request,
        MicroPearl $pearl,
    ): RedirectResponse {
        return $this->transition(
            fn () => $this->workflow->submitForReview(
                $request->user(),
                $pearl,
                $request->integer('edit_version'),
                $request->validated('reason'),
            ),
            'Micro Pearl submitted for review.',
        );
    }

    public function returnToDraft(
        MicroPearlTransitionRequest $request,
        MicroPearl $pearl,
    ): RedirectResponse {
        return $this->transition(
            fn () => $this->workflow->returnToDraft(
                $request->user(),
                $pearl,
                $request->integer('edit_version'),
                $request->validated('reason'),
            ),
            'Micro Pearl returned for revision.',
        );
    }

    public function publish(
        PublishMicroPearlRequest $request,
        MicroPearl $pearl,
    ): RedirectResponse {
        return $this->transition(
            fn () => $this->workflow->publish(
                $request->user(),
                $pearl,
                $request->integer('edit_version'),
                $request->validated('reason'),
                $request->validated('published_at'),
            ),
            'Micro Pearl publication approved.',
        );
    }

    public function retire(
        PublishMicroPearlRequest $request,
        MicroPearl $pearl,
    ): RedirectResponse {
        return $this->transition(
            fn () => $this->workflow->retire(
                $request->user(),
                $pearl,
                $request->integer('edit_version'),
                $request->validated('reason'),
            ),
            'Micro Pearl retired.',
        );
    }

    private function transition(
        callable $operation,
        string $message,
    ): RedirectResponse {
        try {
            $operation();
        } catch (
            InvalidArgumentException
            |LogicException
            $exception
        ) {
            return back()->with(
                'error',
                $exception->getMessage(),
            );
        }

        return back()->with('status', $message);
    }

    private function domains()
    {
        return TosDomain::query()
            ->with([
                'competencies' => fn ($query) => $query
                    ->orderBy('order_index'),
            ])
            ->orderBy('domain_number')
            ->get();
    }
}
