<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="font-display text-xs font-bold uppercase tracking-[0.18em] text-primary">
                    Research Administration
                </p>

                <h2 class="font-display text-xl font-extrabold text-slate-900">
                    Secure Research Import Portal
                </h2>
            </div>

            <a
                href="{{ route('admin.research-forms.index') }}"
                class="rounded-xl border-2 border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25"
            >
                Back to Research Forms
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div
                    role="status"
                    class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800"
                >
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div
                    role="alert"
                    class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800"
                >
                    <p class="font-extrabold">
                        The import could not continue:
                    </p>

                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="rounded-3xl border-2 border-amber-200 bg-amber-50 p-5 text-amber-950">
                <h3 class="font-display font-extrabold">
                    Two-step import protection
                </h3>

                <p class="mt-2 text-sm leading-relaxed">
                    Uploading only performs a dry run. No form content
                    changes until you review the result, provide a
                    reason, and confirm with your current password.
                    The uploaded CSV is kept in private storage and
                    automatically expires.
                </p>

                <p class="mt-2 text-sm font-semibold">
                    Content imports require a Draft version. Validation
                    imports require an In Review version.
                </p>
            </section>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="rounded-3xl border-2 border-slate-200 bg-white p-6 shadow-sm">
                    <p class="font-display text-xs font-bold uppercase tracking-[0.18em] text-primary">
                        Step 1A
                    </p>

                    <h3 class="mt-1 font-display text-lg font-extrabold text-slate-900">
                        Import question content
                    </h3>

                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        Upload the 60-question content CSV into an
                        unserved Draft version. Imported questions
                        remain unvalidated.
                    </p>

                    @if ($contentVersions->isEmpty())
                        <div class="mt-5 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
                            No unserved Draft version is available.
                            Create a new version from the Research Forms
                            page first.
                        </div>
                    @else
                        <form
                            method="POST"
                            action="{{ route('admin.research-form-imports.preview') }}"
                            enctype="multipart/form-data"
                            class="mt-5 space-y-4"
                        >
                            @csrf

                            <input
                                type="hidden"
                                name="import_type"
                                value="content"
                            >

                            <div>
                                <label
                                    for="content_form_version_id"
                                    class="text-sm font-bold text-slate-700"
                                >
                                    Draft form version
                                </label>

                                <select
                                    id="content_form_version_id"
                                    name="form_version_id"
                                    required
                                    class="mt-1 block w-full rounded-xl border-slate-300"
                                >
                                    <option value="">
                                        Select a version
                                    </option>

                                    @foreach ($contentVersions as $version)
                                        <option
                                            value="{{ $version->form_version_id }}"
                                            @selected(
                                                old('form_version_id')
                                                    === $version->form_version_id
                                                && old('import_type')
                                                    === 'content'
                                            )
                                        >
                                            {{ $version->version_code }}
                                            — {{ $version->questions_count }}/60 questions
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label
                                    for="content_csv"
                                    class="text-sm font-bold text-slate-700"
                                >
                                    Content CSV
                                </label>

                                <input
                                    id="content_csv"
                                    name="csv"
                                    type="file"
                                    accept=".csv,text/csv"
                                    required
                                    class="mt-1 block w-full rounded-xl border border-slate-300 bg-white p-2 text-sm"
                                >
                            </div>

                            <button
                                type="submit"
                                class="rounded-xl bg-primary px-4 py-2.5 text-sm font-extrabold text-white transition hover:opacity-90 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25"
                            >
                                Run Content Dry Run
                            </button>
                        </form>
                    @endif
                </section>

                <section class="rounded-3xl border-2 border-slate-200 bg-white p-6 shadow-sm">
                    <p class="font-display text-xs font-bold uppercase tracking-[0.18em] text-primary">
                        Step 1B
                    </p>

                    <h3 class="mt-1 font-display text-lg font-extrabold text-slate-900">
                        Import faculty validation
                    </h3>

                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        Upload the approved faculty-review CSV for an
                        unserved version already placed In Review.
                    </p>

                    @if ($validationVersions->isEmpty())
                        <div class="mt-5 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
                            No unserved In Review version is available.
                            Import content and submit the version for
                            review first.
                        </div>
                    @else
                        <form
                            method="POST"
                            action="{{ route('admin.research-form-imports.preview') }}"
                            enctype="multipart/form-data"
                            class="mt-5 space-y-4"
                        >
                            @csrf

                            <input
                                type="hidden"
                                name="import_type"
                                value="validation"
                            >

                            <div>
                                <label
                                    for="validation_form_version_id"
                                    class="text-sm font-bold text-slate-700"
                                >
                                    In Review version
                                </label>

                                <select
                                    id="validation_form_version_id"
                                    name="form_version_id"
                                    required
                                    class="mt-1 block w-full rounded-xl border-slate-300"
                                >
                                    <option value="">
                                        Select a version
                                    </option>

                                    @foreach ($validationVersions as $version)
                                        <option
                                            value="{{ $version->form_version_id }}"
                                            @selected(
                                                old('form_version_id')
                                                    === $version->form_version_id
                                                && old('import_type')
                                                    === 'validation'
                                            )
                                        >
                                            {{ $version->version_code }}
                                            — {{ $version->questions_count }}/60 questions
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label
                                    for="validator_id"
                                    class="text-sm font-bold text-slate-700"
                                >
                                    Approving reviewer
                                </label>

                                <select
                                    id="validator_id"
                                    name="validator_id"
                                    required
                                    class="mt-1 block w-full rounded-xl border-slate-300"
                                >
                                    <option value="">
                                        Select the actual reviewer
                                    </option>

                                    @foreach ($validators as $validator)
                                        <option
                                            value="{{ $validator->user_id }}"
                                            @selected(
                                                old('validator_id')
                                                    === $validator->user_id
                                            )
                                        >
                                            {{ $validator->fullName() }}
                                            — {{ $validator->role }}
                                            ({{ $validator->email }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label
                                    for="validation_csv"
                                    class="text-sm font-bold text-slate-700"
                                >
                                    Approved validation CSV
                                </label>

                                <input
                                    id="validation_csv"
                                    name="csv"
                                    type="file"
                                    accept=".csv,text/csv"
                                    required
                                    class="mt-1 block w-full rounded-xl border border-slate-300 bg-white p-2 text-sm"
                                >
                            </div>

                            <button
                                type="submit"
                                class="rounded-xl bg-primary px-4 py-2.5 text-sm font-extrabold text-white transition hover:opacity-90 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/25"
                            >
                                Run Validation Dry Run
                            </button>
                        </form>
                    @endif
                </section>
            </div>

            <section class="rounded-3xl border-2 border-slate-200 bg-white p-6 shadow-sm">
                <div>
                    <p class="font-display text-xs font-bold uppercase tracking-[0.18em] text-primary">
                        Step 2
                    </p>

                    <h3 class="mt-1 font-display text-lg font-extrabold text-slate-900">
                        Review and commit
                    </h3>

                    <p class="mt-2 text-sm text-slate-600">
                        Only your own most recent previews are shown.
                        A preview cannot be reused after it is committed,
                        failed, cancelled, or expired.
                    </p>
                </div>

                @if ($imports->isEmpty())
                    <div class="mt-5 rounded-2xl bg-slate-50 p-5 text-sm text-slate-600">
                        No import previews have been created yet.
                    </div>
                @else
                    <div class="mt-6 space-y-5">
                        @foreach ($imports as $import)
                            <article class="rounded-2xl border border-slate-200 p-5">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="font-mono text-sm font-extrabold text-slate-900">
                                            {{ $import->version?->version_code ?? 'Unavailable version' }}
                                        </p>

                                        <p class="mt-1 text-sm text-slate-600">
                                            {{ str($import->import_type)->title() }}
                                            import ·
                                            {{ $import->original_filename }}
                                        </p>

                                        @if ($import->validator)
                                            <p class="mt-1 text-xs text-slate-500">
                                                Reviewer:
                                                {{ $import->validator->fullName() }}
                                            </p>
                                        @endif
                                    </div>

                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-extrabold uppercase tracking-wide text-slate-700">
                                        {{ str($import->status)->replace('_', ' ')->title() }}
                                    </span>
                                </div>

                                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-3">
                                    <div>
                                        <dt class="font-bold text-slate-500">
                                            Uploaded
                                        </dt>
                                        <dd class="text-slate-900">
                                            {{ $import->created_at->format('M j, Y g:i A') }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt class="font-bold text-slate-500">
                                            Expires
                                        </dt>
                                        <dd class="text-slate-900">
                                            {{ $import->expires_at->format('M j, Y g:i A') }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt class="font-bold text-slate-500">
                                            Size
                                        </dt>
                                        <dd class="text-slate-900">
                                            {{ number_format($import->file_size / 1024, 1) }} KB
                                        </dd>
                                    </div>
                                </dl>

                                @if (filled($import->preview_output))
                                    <details class="mt-4 rounded-xl bg-slate-950 p-4 text-slate-100">
                                        <summary class="cursor-pointer text-sm font-bold">
                                            View dry-run result
                                        </summary>

                                        <pre class="mt-3 max-h-72 overflow-auto whitespace-pre-wrap text-xs leading-relaxed">{{ $import->preview_output }}</pre>
                                    </details>
                                @endif

                                @if ($import->failure_message)
                                    <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                                        {{ $import->failure_message }}
                                    </div>
                                @endif

                                @if ($import->canCommit())
                                    <form
                                        method="POST"
                                        action="{{ route('admin.research-form-imports.commit', $import) }}"
                                        class="mt-5 grid gap-4 rounded-2xl border-2 border-amber-200 bg-amber-50 p-4 md:grid-cols-2"
                                    >
                                        @csrf

                                        <input
                                            type="hidden"
                                            name="expected_lock_version"
                                            value="{{ $import->lock_version }}"
                                        >

                                        <div>
                                            <label
                                                for="reason_{{ $import->import_id }}"
                                                class="text-sm font-bold text-slate-700"
                                            >
                                                Import reason
                                            </label>

                                            <textarea
                                                id="reason_{{ $import->import_id }}"
                                                name="reason"
                                                rows="3"
                                                required
                                                minlength="10"
                                                maxlength="500"
                                                class="mt-1 block w-full rounded-xl border-slate-300"
                                                placeholder="Explain why this reviewed file is being imported."
                                            ></textarea>
                                        </div>

                                        <div>
                                            <label
                                                for="password_{{ $import->import_id }}"
                                                class="text-sm font-bold text-slate-700"
                                            >
                                                Current password
                                            </label>

                                            <input
                                                id="password_{{ $import->import_id }}"
                                                name="current_password"
                                                type="password"
                                                required
                                                autocomplete="current-password"
                                                class="mt-1 block w-full rounded-xl border-slate-300"
                                            >

                                            <button
                                                type="submit"
                                                class="mt-3 rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-extrabold text-white transition hover:bg-amber-700 focus:outline-none focus-visible:ring-4 focus-visible:ring-amber-300"
                                            >
                                                Confirm Import
                                            </button>
                                        </div>
                                    </form>

                                    <form
                                        method="POST"
                                        action="{{ route('admin.research-form-imports.destroy', $import) }}"
                                        class="mt-3"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="text-sm font-bold text-red-700 underline decoration-2 underline-offset-4"
                                        >
                                            Cancel and delete private upload
                                        </button>
                                    </form>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
