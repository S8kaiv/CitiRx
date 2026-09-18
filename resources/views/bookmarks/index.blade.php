<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-display text-xl font-bold text-slate-900 leading-tight">
                    {{ __('Bookmarks') }}
                </h2>
                <p class="text-xs font-semibold uppercase tracking-wider text-muted-ink">
                    Personal Question Study Notebook
                </p>
            </div>

            <a href="{{ route('dashboard') }}" 
               class="font-display text-xs font-bold text-muted-ink hover:text-primary transition">
                Return to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="pt-6 pb-16 font-sans text-slate-900 antialiased">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 space-y-6">

            {{-- Status Notification Banner --}}
            @if (session('status'))
                <div role="status" class="flex items-center gap-3 rounded-2xl border-2 border-b-4 border-primary/30 bg-primary-tint/60 p-4 font-display text-xs font-bold text-primary shadow-sm">
                    <span class="text-base">ℹ️</span>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            {{-- TOP HERO CARD: BOOKMARKS SUMMARY --}}
            <div class="relative overflow-hidden rounded-[1.5rem] border-2 border-b-4 border-primary/30 bg-gradient-to-br from-primary-tint via-[#FAF8FF] to-white p-6 sm:p-8 shadow-sm">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
                    <div class="flex items-center gap-5 text-center sm:text-left">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl border-2 border-b-4 border-[#F5A623]/40 bg-gold-tint text-3xl shadow-sm">
                            ⭐
                        </div>
                        <div>
                            <span class="inline-flex items-center rounded-full bg-white px-3 py-0.5 font-display text-[11px] font-bold text-primary shadow-sm ring-1 ring-primary/20">
                                Remediation Notebook
                            </span>
                            <h3 class="mt-1 font-display text-2xl font-extrabold text-slate-900 sm:text-3xl">
                                {{ $total }} Starred {{ \Illuminate\Support\Str::plural('Question', $total) }}
                            </h3>
                            <p class="text-xs font-medium text-slate-600 mt-0.5">
                                Review flagged items from your practice drills and record personal study notes.
                            </p>
                        </div>
                    </div>

                    <div class="shrink-0">
                        <a href="{{ route('practice.intro') }}"
                           style="--lip: #4A2FC4;"
                           class="btn-press inline-flex items-center rounded-2xl bg-primary px-6 py-3 font-display text-sm font-bold text-white shadow-md transition hover:bg-primary/95">
                            Practice Questions
                        </a>
                    </div>
                </div>
            </div>

            {{-- EMPTY STATE --}}
            @if ($grouped->isEmpty())
                <div class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-12 text-center shadow-sm">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl border-2 border-slate-200 bg-slate-50 text-3xl text-slate-400">
                        ☆
                    </div>
                    <h3 class="mt-4 font-display text-lg font-bold text-slate-900">
                        No bookmarks saved yet
                    </h3>
                    <p class="mt-1 text-xs text-muted-ink max-w-sm mx-auto">
                        Click the star icon (★) while taking practice sessions to pin challenging questions to this review list.
                    </p>
                    <div class="mt-6">
                        <a href="{{ route('practice.intro') }}"
                           style="--lip: #4A2FC4;"
                           class="btn-press inline-flex items-center rounded-xl bg-primary px-6 py-2.5 font-display text-xs font-bold uppercase tracking-wider text-white">
                            Start Practice Session
                        </a>
                    </div>
                </div>
            @else

                {{-- DOMAIN GROUPS ACCORDION / TILES --}}
                <div class="space-y-6">
                    @foreach ($grouped as $group)
                        <div class="rounded-2xl border-2 border-b-4 border-slate-200 bg-white shadow-sm overflow-hidden">
                            
                            {{-- Group Domain Header --}}
                            <div class="flex items-center justify-between border-b-2 border-slate-100 bg-slate-50/70 px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="rounded-xl bg-clinical-tint px-3 py-1 font-display text-xs font-bold text-clinical-ink">
                                        {{ $group['domain_name'] }}
                                    </span>
                                    <span class="text-xs font-semibold text-muted-ink">
                                        {{ $group['bookmarks']->count() }} {{ \Illuminate\Support\Str::plural('question', $group['bookmarks']->count()) }}
                                    </span>
                                </div>
                            </div>

                            {{-- Question List inside Domain --}}
                            <div class="divide-y-2 divide-slate-100">
                                @foreach ($group['bookmarks'] as $bookmark)
                                    @php
                                        $q = $bookmark->question;
                                        $correctChoice = $q->correctChoice;
                                    @endphp

                                    <div class="p-6 space-y-4">
                                        {{-- Question Top Meta & Remove Button --}}
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 font-display text-[11px] font-bold text-slate-600">
                                                    {{ $q->competency->title }}
                                                </span>
                                                <h4 class="mt-2 font-sans text-base font-bold text-slate-900 leading-snug">
                                                    {{ $q->question_text }}
                                                </h4>
                                            </div>

                                            {{-- Remove Bookmark Form --}}
                                            <form method="POST"
                                                  action="{{ route('bookmarks.destroy', ['bookmark' => $bookmark->bookmark_id]) }}"
                                                  class="shrink-0">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" 
                                                        title="Remove from bookmarks"
                                                        class="flex items-center gap-1 rounded-xl border border-weak/30 bg-weak-tint/50 px-2.5 py-1 font-display text-xs font-bold text-weak-ink hover:bg-weak-tint transition">
                                                    <span>✕</span>
                                                    <span>Remove</span>
                                                </button>
                                            </form>
                                        </div>

                                        {{-- Correct Answer Banner --}}
                                        @if ($correctChoice)
                                            <div class="rounded-xl border border-strong/30 bg-strong-tint/60 p-3.5 text-xs text-strong-ink">
                                                <span class="font-display font-bold uppercase tracking-wider block text-[10px] opacity-80 mb-0.5">
                                                    Correct Key
                                                </span>
                                                <strong>{{ $correctChoice->choice_letter }}.</strong> {{ $correctChoice->choice_text }}
                                            </div>
                                        @endif

                                        {{-- Clinical Rationale --}}
                                        @if ($q->hypercorrection_rationale)
                                            <div class="rounded-xl border border-clinical/20 bg-clinical-tint/40 p-3.5 text-xs text-slate-700 leading-relaxed">
                                                <span class="font-display font-bold uppercase tracking-wider text-clinical-ink block text-[10px] mb-0.5">
                                                    Clinical Rationale
                                                </span>
                                                {{ $q->hypercorrection_rationale }}
                                            </div>
                                        @endif

                                        {{-- PERSONAL STUDY NOTES FORM --}}
                                        <form method="POST"
                                              action="{{ route('bookmarks.updateNotes', ['bookmark' => $bookmark->bookmark_id]) }}"
                                              class="rounded-xl border-2 border-slate-100 bg-slate-50/50 p-4 space-y-3">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="bookmark_id" value="{{ $bookmark->bookmark_id }}">

                                            <div class="flex items-center justify-between">
                                                <label for="notes-{{ $bookmark->bookmark_id }}" 
                                                       class="font-display text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                                                    <span>📝</span>
                                                    <span>My Study Notes</span>
                                                </label>
                                                <span class="text-[10px] font-semibold text-muted-ink">Auto-saved to Vault</span>
                                            </div>

                                            <textarea id="notes-{{ $bookmark->bookmark_id }}" 
                                                      name="personal_notes" 
                                                      rows="2" 
                                                      maxlength="2000"
                                                      class="w-full rounded-xl border-2 border-slate-200 bg-white p-3 text-xs font-medium text-slate-800 shadow-sm placeholder:text-slate-400 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                                      placeholder="Write mnemonics, key formulas, or drug interactions to remember...">{{ old('bookmark_id') === $bookmark->bookmark_id ? old('personal_notes') : $bookmark->personal_notes }}</textarea>

                                            @if (old('bookmark_id') === $bookmark->bookmark_id)
                                                @error('personal_notes')
                                                    <p class="text-xs font-semibold text-weak-ink">
                                                        {{ $message }}
                                                    </p>
                                                @enderror
                                            @endif

                                            <div class="flex justify-end">
                                                <button type="submit"
                                                        style="--lip: #4A2FC4;"
                                                        class="btn-press rounded-xl bg-primary px-4 py-2 font-display text-xs font-bold text-white shadow-sm transition hover:bg-primary/95">
                                                    Save Notes
                                                </button>
                                            </div>
                                        </form>

                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- BOTTOM NAVIGATION BUTTONS --}}
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-4">
                <a href="{{ route('practice.intro') }}"
                   style="--lip: #4A2FC4;"
                   class="btn-press w-full sm:w-auto rounded-xl bg-primary px-8 py-3 text-center font-display text-sm font-bold text-white shadow-sm transition hover:bg-primary/95">
                    Practice Now
                </a>

                <a href="{{ route('dashboard') }}"
                   style="--lip: #CBD5E1;"
                   class="btn-press w-full sm:w-auto rounded-xl border-2 border-slate-200 bg-white px-8 py-3 text-center font-display text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                    Back to Dashboard
                </a>
            </div>

        </div>
    </div>
</x-app-layout>