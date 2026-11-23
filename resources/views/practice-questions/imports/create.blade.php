<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Import practice questions" description="Add multiple questions to adaptive practice, separate from research Forms A and B.">
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route($routePrefix.'.index')">Back to practice questions</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="mx-auto max-w-4xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        <x-ui.card>
            <h2 class="text-lg font-bold text-ink">Upload questions</h2>
            <p class="mt-2 text-sm leading-relaxed text-ink-muted">Preview your CSV, check the answers, then select the rows to import. Nothing is added until you confirm. Imports add new questions; do not include rows you have already imported.</p>
            <form method="POST" enctype="multipart/form-data" action="{{ route($routePrefix.'.imports.preview') }}" class="mt-5 space-y-4">
                @csrf
                <x-ui.field name="csv" type="file" label="Practice question CSV" accept=".csv,text/csv" hint="CSV UTF-8, comma-delimited. Up to 200 questions and 2 MB per file." required />
                <x-ui.button type="submit" class="w-full sm:w-auto">Validate and preview</x-ui.button>
            </form>
        </x-ui.card>

        <x-ui.card>
            <h2 class="text-lg font-bold text-ink">Prepare your CSV</h2>
            <p class="mt-2 text-sm leading-relaxed text-ink-muted">Keep the template headers and enter one question per row. Save as CSV UTF-8 (Comma delimited), not XLSX. Excel can wrap text for editing; CSV does not store cell widths or formatting.</p>
            <x-ui.button variant="secondary" :href="route($routePrefix.'.imports.template')" class="mt-4 w-full sm:w-auto">Download practice CSV template</x-ui.button>
            <dl class="mt-5 space-y-4 text-sm text-ink">
                <div><dt class="font-bold">competency_code</dt><dd class="mt-1 text-ink-muted">Use an exact code from the competency reference below, such as PC-01. Do not enter a competency ID or domain name.</dd></div>
                <div><dt class="font-bold">question_text, choice_a, choice_b, choice_c, choice_d</dt><dd class="mt-1 text-ink-muted">Question: up to 2,000 characters. Each of the four different choices: up to 1,000 characters. Let your spreadsheet quote cells containing commas or line breaks.</dd></div>
                <div><dt class="font-bold">correct_choice, rationale</dt><dd class="mt-1 text-ink-muted">Correct answer: A, B, C, or D. Explain why it is correct in the rationale (up to 2,000 characters).</dd></div>
                <div><dt class="font-bold">cognitive_level (optional)</dt><dd class="mt-1 text-ink-muted">Leave blank or use: {{ implode(', ', \App\Models\Question::COGNITIVE_LEVELS) }}.</dd></div>
                <div><dt class="font-bold">source_reference, copyright_permission</dt><dd class="mt-1 text-ink-muted">Identify the author and supporting references (up to 2,000 characters). Use exactly one rights value: permission confirmed, public domain, or licensed. Use permission confirmed for your own original content only when you hold the necessary rights.</dd></div>
            </dl>
            <details class="mt-5 border-t border-line pt-3">
                <summary class="flex min-h-11 cursor-pointer items-center font-bold text-ink">Competency reference</summary>
                <div class="mt-3 space-y-4 text-sm">
                    @foreach ($domains as $domain)
                        <section>
                            <h3 class="font-bold text-ink">{{ $domain->domain_number }}. {{ $domain->domain_name }}</h3>
                            <ul class="mt-2 space-y-2 text-ink-muted">
                                @foreach ($domain->competencies as $competency)
                                    <li class="break-words"><span class="font-semibold text-ink">{{ $competency->competency_code }}</span> — {{ $competency->title }}</li>
                                @endforeach
                            </ul>
                        </section>
                    @endforeach
                </div>
            </details>
        </x-ui.card>
    </div>
</x-app-layout>
