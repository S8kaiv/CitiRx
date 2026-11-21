<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\PublishMicroPearlRequest;
use App\Http\Requests\Content\RetireMicroPearlRequest;
use App\Http\Requests\Content\ReturnMicroPearlToDraftRequest;
use App\Http\Requests\Content\SaveMicroPearlRequest;
use App\Http\Requests\Content\SubmitMicroPearlForReviewRequest;
use App\Models\MicroPearl;
use App\Models\TosDomain;
use App\Services\MicroPearlWorkflowService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
            ->when($request->user()->role === 'faculty', fn ($query) => $query->where(fn ($query) => $query
                ->where('content_status', '!=', MicroPearl::STATUS_DRAFT)->orWhere('created_by', $request->user()->user_id)))
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

        $publishableIds = $pearls->getCollection()
            ->filter(fn (MicroPearl $pearl) => $this->workflow->canPublish($request->user(), $pearl))
            ->modelKeys();

        return view(
            'content.pearls.index',
            compact(
                'pearls',
                'status',
                'search',
                'publishableIds',
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
        abort_if(auth()->user()->role === 'faculty' && $pearl->content_status === MicroPearl::STATUS_DRAFT
            && $pearl->created_by !== auth()->id(), 403);
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
        SubmitMicroPearlForReviewRequest $request,
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
        ReturnMicroPearlToDraftRequest $request,
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
        $isOwnAdministratorPearl = in_array($request->user()->role, ['admin', 'dean'], true)
            && $pearl->created_by === $request->user()->user_id;

        return $this->transition(
            fn () => $this->workflow->publish(
                $request->user(),
                $pearl,
                $request->integer('edit_version'),
                $isOwnAdministratorPearl
                    ? 'Published by the administrator who authored this Micro Pearl.'
                    : 'Published after independent content review.',
                $request->validated('published_at'),
            ),
            $isOwnAdministratorPearl
                ? 'Micro Pearl published.'
                : 'Micro Pearl publication approved.',
        );
    }

    public function bulkPublish(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('bulkPublish', [
            'pearls' => ['required', 'array', 'min:1', 'max:25'],
            'pearls.*' => ['required', 'integer', 'min:1'],
            'current_password' => ['required', 'string', 'current_password'],
        ]);
        $versions = $validated['pearls'];

        foreach (array_keys($versions) as $id) {
            if (! is_string($id) || ! Str::isUuid($id)) {
                return back()->with('error', 'Select valid Micro Pearls to publish.');
            }
        }

        $reviewer = $request->user();
        ksort($versions);

        try {
            DB::transaction(function () use ($reviewer, $versions): void {
                $pearls = MicroPearl::query()->whereKey(array_keys($versions))->get()->keyBy('pearl_id');

                foreach ($versions as $id => $version) {
                    $pearl = $pearls->get($id);
                    if ($pearl === null) {
                        throw new LogicException('A selected Micro Pearl is no longer available. Refresh the list.');
                    }

                    try {
                        $this->workflow->publish(
                            $reviewer,
                            $pearl,
                            (int) $version,
                            'Published from the Micro Pearl list after content review.',
                            null,
                        );
                    } catch (ValidationException $exception) {
                        throw new LogicException($pearl->title.': '.$exception->getMessage(), previous: $exception);
                    }
                }
            });
        } catch (LogicException $exception) {
            return back()
                ->withInput($request->only('pearls'))
                ->with('error', 'Publication canceled for the selected batch. '.$exception->getMessage());
        }

        return back()->with('status', count($versions).' Micro Pearl(s) published.');
    }

    public function retire(
        RetireMicroPearlRequest $request,
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
