<?php

namespace App\Http\Controllers;

use App\Models\AssessmentSession;
use App\Models\Question;
use App\Models\QuestionBookmark;
use App\Services\BookmarkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookmarkController extends Controller
{
    public function __construct(
        protected BookmarkService $bookmarks,
    ) {}

    public function index(Request $request): View
    {
        $bookmarks = QuestionBookmark::query()
            ->where('user_id', $request->user()->user_id)
            ->with([
                'question.correctChoice',
                'question.competency.domain',
            ])
            ->orderByDesc('created_at')
            ->get();

        $grouped = $bookmarks
            ->groupBy(
                fn ($bookmark) => $bookmark
                    ->question
                    ->competency
                    ->domain
                    ->domain_id
            )
            ->sortBy(
                fn ($group) => $group
                    ->first()
                    ->question
                    ->competency
                    ->domain
                    ->domain_number
            )
            ->map(function ($group) {
                $domain = $group
                    ->first()
                    ->question
                    ->competency
                    ->domain;

                return [
                    'domain_id' => $domain->domain_id,
                    'domain_name' => $domain->domain_name,
                    'domain_number' => $domain->domain_number,
                    'bookmarks' => $group,
                ];
            })
            ->values();

        return view('bookmarks.index', [
            'grouped' => $grouped,
            'total' => $bookmarks->count(),
        ]);
    }

    public function store(
        Request $request,
        Question $question
    ): RedirectResponse {
        $session = $this->practiceSessionFor(
            $request,
            $question
        );

        $this->bookmarks->add(
            $request->user(),
            $question
        );

        return $this->redirectAfterBookmark(
            $session,
            'Bookmark saved.'
        );
    }

    public function destroyQuestion(
        Request $request,
        Question $question
    ): RedirectResponse {
        $session = $this->practiceSessionFor(
            $request,
            $question
        );

        $this->bookmarks->remove(
            $request->user(),
            $question
        );

        return $this->redirectAfterBookmark(
            $session,
            'Bookmark removed.'
        );
    }

    public function updateNotes(
        Request $request,
        QuestionBookmark $bookmark
    ): RedirectResponse {
        abort_unless(
            $bookmark->user_id
                === $request->user()->user_id,
            403
        );

        $validated = $request->validate([
            'personal_notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $this->bookmarks->updateNotes(
            $bookmark,
            $validated['personal_notes'] ?? null
        );

        return redirect()
            ->route('bookmarks.index')
            ->with('status', 'Notes saved.');
    }

    public function destroy(
        Request $request,
        QuestionBookmark $bookmark
    ): RedirectResponse {
        abort_unless(
            $bookmark->user_id
                === $request->user()->user_id,
            403
        );

        $bookmark->delete();

        return redirect()
            ->route('bookmarks.index')
            ->with('status', 'Bookmark removed.');
    }

    private function practiceSessionFor(
        Request $request,
        Question $question
    ): AssessmentSession {
        $validated = $request->validate([
            'practice_session_id' => [
                'required',
                'uuid',
            ],
        ]);

        $session = AssessmentSession::query()
            ->whereKey(
                $validated['practice_session_id']
            )
            ->where(
                'user_id',
                $request->user()->user_id
            )
            ->where('session_type', 'practice')
            ->firstOrFail();

        abort_unless(
            in_array(
                $question->question_id,
                $session->served_question_ids ?? [],
                true
            ),
            404
        );

        return $session;
    }

    private function redirectAfterBookmark(
        AssessmentSession $session,
        string $message
    ): RedirectResponse {
        $route = $session->completed_at === null
            ? 'practice.show'
            : 'practice.summary';

        return redirect()
            ->route($route, [
                'session' => $session->session_id,
            ])
            ->with('status', $message);
    }
}
