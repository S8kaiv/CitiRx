<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-display text-xl font-extrabold text-slate-900">
                    Question Detail
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Version {{ $question->edit_version }}
                </p>
            </div>

            <a
                href="{{ route('admin.questions.index') }}"
                class="rounded-xl border-2 border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50"
            >
                Back to Question Bank
            </a>
        </div>
    </x-slot>

    @php
        $displayAuditValue = static function (mixed $value): string {
            if ($value === null || $value === '') {
                return '(empty)';
            }

            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }

            if (is_array($value)) {
                return json_encode(
                    $value,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                );
            }

            return (string) $value;
        };
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-5 sm:px-6 lg:px-8">
            @if (session('status'))
                <div
                    role="status"
                    class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800"
                >
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div
                    role="alert"
                    class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-800"
                >
                    {{ session('error') }}
                </div>
            @endif

            @if ($isLocked)
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                    <h3 class="font-display font-extrabold text-amber-900">
                        Locked question
                    </h3>

                    <p class="mt-1 text-sm text-amber-800">
                        {{ $lockReason }}
                    </p>
                </div>
            @else
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                    <div>
                        <h3 class="font-display font-extrabold text-emerald-900">
                            Editable draft
                        </h3>

                        <p class="mt-1 text-sm text-emerald-800">
                            This question has not been served or validated.
                        </p>
                    </div>

                    <a
                        href="{{ route('admin.questions.edit', [
                            'question' => $question->question_id,
                        ]) }}"
                        class="rounded-xl border-2 border-b-4 border-primary/30 bg-primary px-4 py-2 text-sm font-bold text-white hover:bg-[#4A2FC4]"
                    >
                        Edit Question
                    </a>
                </div>
            @endif

            <section class="rounded-3xl border-2 border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap gap-2 text-xs font-bold">
                    <span class="rounded-full bg-violet-100 px-3 py-1 text-violet-700">
                        {{ $question->research_form ?? 'Practice question' }}
                    </span>

                    @if ($question->form_position !== null)
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-700">
                            Position {{ $question->form_position }}
                        </span>
                    @endif

                    <span class="rounded-full px-3 py-1 {{ $question->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                        {{ $question->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <div class="mt-4 text-sm text-slate-500">
                    {{ $question->competency?->domain?->domain_name ?? 'Unknown domain' }}
                    <span aria-hidden="true">→</span>
                    {{ $question->competency?->competency_code }}
                    —
                    {{ $question->competency?->title }}
                </div>

                <h3 class="mt-5 text-lg font-bold leading-relaxed text-slate-900">
                    {{ $question->question_text }}
                </h3>

                <div class="mt-6 space-y-3">
                    @foreach ($question->choices as $choice)
                        <div class="flex gap-3 rounded-2xl border-2 p-4 {{ $choice->is_correct ? 'border-emerald-300 bg-emerald-50' : 'border-slate-200 bg-white' }}">
                            <span class="font-display font-extrabold text-slate-700">
                                {{ $choice->choice_letter }}.
                            </span>

                            <div class="flex-1">
                                <p class="text-sm text-slate-900">
                                    {{ $choice->choice_text }}
                                </p>

                                @if ($choice->is_correct)
                                    <p class="mt-1 text-xs font-bold uppercase tracking-wide text-emerald-700">
                                        Correct answer
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <dl class="mt-6 grid gap-4 border-t border-slate-100 pt-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">
                            Cognitive level
                        </dt>

                        <dd class="mt-1 text-sm font-semibold text-slate-900">
                            {{ $question->cognitive_level
                                ? ucfirst($question->cognitive_level)
                                : 'Not assigned' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">
                            Difficulty index
                        </dt>

                        <dd class="mt-1 text-sm font-semibold text-slate-900">
                            {{ $question->difficulty_index_p ?? 'Not calculated' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">
                            First served
                        </dt>

                        <dd class="mt-1 text-sm font-semibold text-slate-900">
                            {{ $question->first_served_at?->format('M j, Y g:i A') ?? 'Never' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">
                            Research validation
                        </dt>

                        <dd class="mt-1 text-sm font-semibold text-slate-900">
                            @if (
                                $question->research_validated_at
                                && $question->research_validated_by
                            )
                                {{ $question->research_validated_at->format('M j, Y') }}
                                by
                                {{ $question->researchValidator?->fullName() ?? 'Unknown reviewer' }}
                            @elseif (
                                $question->research_validated_at
                                || $question->research_validated_by
                            )
                                <span class="text-red-700">
                                    Incomplete validation metadata
                                </span>
                            @else
                                Not validated
                            @endif
                        </dd>
                    </div>
                </dl>

                <div class="mt-6 rounded-2xl border border-blue-200 bg-blue-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-blue-700">
                        Rationale
                    </p>

                    <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-blue-950">
                        {{ $question->hypercorrection_rationale ?? 'No rationale supplied.' }}
                    </p>
                </div>
            </section>

            <section class="rounded-3xl border-2 border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-display text-lg font-extrabold text-slate-900">
                    Availability
                </h3>

                @if ($question->is_active)
                    <form
                        method="POST"
                        action="{{ route('admin.questions.status', [
                            'question' => $question->question_id,
                        ]) }}"
                        class="mt-4 space-y-3"
                    >
                        @csrf
                        @method('PATCH')

                        <input
                            type="hidden"
                            name="active"
                            value="0"
                        >

                        <input
                            type="hidden"
                            name="edit_version"
                            value="{{ $question->edit_version }}"
                        >

                        <label
                            for="change_reason"
                            class="block text-sm font-bold text-slate-700"
                        >
                            Reason for deactivation
                        </label>

                        <textarea
                            id="change_reason"
                            name="change_reason"
                            rows="2"
                            required
                            minlength="5"
                            maxlength="500"
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-primary focus:ring-primary"
                            placeholder="Explain why this question should no longer be served."
                        >{{ old('change_reason') }}</textarea>

                        @error('change_reason')
                            <p class="text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                        <button
                            type="submit"
                            class="rounded-xl border-2 border-b-4 border-red-700 bg-red-600 px-4 py-2 text-sm font-bold text-white hover:bg-red-700"
                        >
                            Deactivate Question
                        </button>
                    </form>
                @elseif ($canReactivate)
                    <form
                        method="POST"
                        action="{{ route('admin.questions.status', [
                            'question' => $question->question_id,
                        ]) }}"
                        class="mt-4 space-y-3"
                    >
                        @csrf
                        @method('PATCH')

                        <input
                            type="hidden"
                            name="active"
                            value="1"
                        >

                        <input
                            type="hidden"
                            name="edit_version"
                            value="{{ $question->edit_version }}"
                        >

                        <label
                            for="change_reason"
                            class="block text-sm font-bold text-slate-700"
                        >
                            Reason for reactivation
                        </label>

                        <textarea
                            id="change_reason"
                            name="change_reason"
                            rows="2"
                            required
                            minlength="5"
                            maxlength="500"
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-primary focus:ring-primary"
                            placeholder="Explain why this draft question should return to the pool."
                        >{{ old('change_reason') }}</textarea>

                        <button
                            type="submit"
                            class="rounded-xl border-2 border-b-4 border-emerald-700 bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700"
                        >
                            Reactivate Question
                        </button>
                    </form>
                @else
                    <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        This locked question was permanently retired. Create and validate a replacement question if it is needed again.
                    </div>
                @endif
            </section>

            <section class="rounded-3xl border-2 border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-display text-lg font-extrabold text-slate-900">
                    Audit History
                </h3>

                <div class="mt-5 space-y-4">
                    @forelse ($history as $edit)
                        <article class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                <span class="font-bold text-slate-800">
                                    {{ str($edit->action)->replace('_', ' ')->title() }}
                                </span>

                                <span aria-hidden="true">•</span>

                                <span>
                                    {{ $edit->editor?->fullName() ?? 'Unknown administrator' }}
                                </span>

                                <span aria-hidden="true">•</span>

                                <time datetime="{{ $edit->edited_at->toIso8601String() }}">
                                    {{ $edit->edited_at->format('M j, Y g:i A') }}
                                </time>

                                <span aria-hidden="true">•</span>

                                <span>
                                    Version {{ $edit->version_before }}
                                    →
                                    {{ $edit->version_after }}
                                </span>
                            </div>

                            @if ($edit->reason)
                                <p class="mt-2 text-sm text-slate-700">
                                    <strong>Reason:</strong>
                                    {{ $edit->reason }}
                                </p>
                            @endif

                            <div class="mt-3 space-y-3">
                                @foreach ($edit->changes as $field => $change)
                                    <div class="rounded-xl bg-slate-50 p-3">
                                        <p class="font-mono text-xs font-bold text-slate-600">
                                            {{ $field }}
                                        </p>

                                        <div class="mt-2 grid gap-2 text-xs sm:grid-cols-2">
                                            <div class="rounded-lg bg-red-50 p-2 text-red-800">
                                                <strong>Before:</strong>
                                                {{ \Illuminate\Support\Str::limit(
                                                    $displayAuditValue($change['old'] ?? null),
                                                    250,
                                                ) }}
                                            </div>

                                            <div class="rounded-lg bg-emerald-50 p-2 text-emerald-800">
                                                <strong>After:</strong>
                                                {{ \Illuminate\Support\Str::limit(
                                                    $displayAuditValue($change['new'] ?? null),
                                                    250,
                                                ) }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </article>
                    @empty
                        <p class="text-sm text-slate-500">
                            No audited browser edits have been recorded.
                        </p>
                    @endforelse
                </div>

                @if ($history->hasPages())
                    <div class="mt-5 border-t border-slate-100 pt-4">
                        {{ $history->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
