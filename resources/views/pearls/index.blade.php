<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="font-display text-xl font-black leading-tight text-slate-900">
                Micro Pearls
            </h1>

            <p class="mt-1 font-display text-[11px] font-bold uppercase tracking-wider text-muted-ink">
                Short, reviewed lessons for focused PhLE recall.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">

            <form method="GET" action="{{ route('pearls.index') }}"
                class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-5">
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label for="pearl-search"
                            class="mb-1 block font-display text-xs font-bold uppercase tracking-wider text-slate-600">
                            Search
                        </label>

                        <input id="pearl-search" type="search" name="q" value="{{ $filters['q'] }}"
                            maxlength="100" placeholder="Search a topic or keyword"
                            class="w-full rounded-2xl border-2 border-slate-200 text-sm focus:border-primary focus:ring-primary">
                    </div>

                    <div>
                        <label for="domain-filter"
                            class="mb-1 block font-display text-xs font-bold uppercase tracking-wider text-slate-600">
                            Domain
                        </label>

                        <select id="domain-filter" name="domain_id"
                            onchange="
                                this.form.elements['competency_id'].value = '';
                                this.form.submit();
                            "
                            class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                            <option value="">
                                All domains
                            </option>

                            @foreach ($domains as $domain)
                                <option value="{{ $domain->domain_id }}" @selected((string) $filters['domain_id'] === (string) $domain->domain_id)>
                                    Domain {{ $domain->domain_number }}
                                    — {{ $domain->domain_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="competency-filter" class="block text-sm font-semibold text-slate-700">
                            Competency
                        </label>

                        @php
                            $competencyDomains =
                                $filters['domain_id'] === null
                                    ? $domains
                                    : $domains->whereStrict('domain_id', (int) $filters['domain_id']);
                        @endphp

                        <select id="competency-filter" name="competency_id" onchange="this.form.submit()"
                            class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                            <option value="">
                                All competencies
                            </option>

                            @foreach ($competencyDomains as $domain)
                                <optgroup label="{{ $domain->domain_name }}">
                                    @foreach ($domain->competencies as $competency)
                                        <option value="{{ $competency->competency_id }}" @selected((string) $filters['competency_id'] === (string) $competency->competency_id)>
                                            {{ $competency->competency_code }}
                                            — {{ $competency->title }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <button type="submit"
                        class="rounded-2xl border-2 border-b-4 border-primary-dark bg-primary px-5 py-2.5 font-display text-sm font-bold text-white transition hover:-translate-y-0.5">
                        Apply Filters
                    </button>

                    <a href="{{ route('pearls.index') }}"
                        class="rounded-2xl border-2 border-b-4 border-slate-200 bg-white px-5 py-2.5 font-display text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                        Clear
                    </a>

                    <span class="ml-auto text-sm font-semibold text-muted-ink">
                        {{ $pearls->total() }}
                        {{ \Illuminate\Support\Str::plural('pearl', $pearls->total()) }}
                    </span>
                </div>
            </form>

            @if ($pearls->isEmpty())
                <section class="rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-10 text-center">
                    <div class="text-5xl" aria-hidden="true">
                        📖
                    </div>

                    <h2 class="mt-4 font-display text-xl font-extrabold text-slate-900">
                        No published pearls found
                    </h2>

                    <p class="mx-auto mt-2 max-w-lg text-sm text-muted-ink">
                        Try clearing the filters. If the library is still empty,
                        its lessons may still be awaiting content review.
                    </p>
                </section>
            @else
                <div class="grid gap-5 md:grid-cols-2">
                    @foreach ($pearls as $pearl)
                        <article class="flex flex-col rounded-3xl border-2 border-b-4 border-slate-200 bg-white p-6">
                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                <span
                                    class="rounded-full bg-primary-tint px-3 py-1 font-display font-bold text-primary">
                                    {{ $pearl->competency->competency_code }}
                                </span>

                                <span class="text-muted-ink">
                                    Domain {{ $pearl->competency->domain->domain_number }}
                                </span>

                                @if ($pearl->is_ai_generated)
                                    <span class="rounded-full bg-sky-50 px-3 py-1 font-display font-bold text-sky-700">
                                        AI-assisted · reviewed
                                    </span>
                                @endif
                            </div>

                            <h2 class="mt-4 font-display text-xl font-extrabold leading-snug text-slate-900">
                                {{ $pearl->title }}
                            </h2>

                            <p class="mt-2 text-xs font-semibold text-muted-ink">
                                {{ $pearl->competency->title }}
                                · about {{ $pearl->estimated_read_seconds }} seconds
                            </p>

                            <p class="mt-4 flex-1 text-sm leading-7 text-slate-700">
                                {{ \Illuminate\Support\Str::limit($pearl->mnemonic_summary, 190) }}
                            </p>

                            <a href="{{ route('pearls.show', $pearl) }}"
                                class="mt-5 inline-flex items-center justify-center rounded-2xl border-2 border-b-4 border-primary-dark bg-primary px-4 py-2.5 font-display text-sm font-bold text-white transition hover:-translate-y-0.5">
                                Read Pearl
                            </a>
                        </article>
                    @endforeach
                </div>

                <div>
                    {{ $pearls->links() }}
                </div>
            @endif

            <div class="text-center">
                <a href="{{ route('dashboard') }}"
                    class="font-display text-sm font-bold text-primary hover:text-primary-dark">
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
