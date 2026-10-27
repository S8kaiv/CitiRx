<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-xl font-bold">
            Bulk Cohort Assignment
        </h1>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4">
            @if (session('status'))
                <div class="rounded-xl bg-green-50 p-4 text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <section class="rounded-3xl border-2 border-slate-200 bg-white p-6">
                <h2 class="font-display text-lg font-bold">
                    Required CSV header
                </h2>

                <pre class="mt-3 overflow-x-auto rounded-xl bg-slate-900 p-4 text-sm text-white">email,student_id,role,cohort_name,academic_year</pre>

                <ul class="mt-4 list-disc space-y-2 pl-5 text-sm text-slate-600">
                    <li>Only existing student and faculty accounts are accepted.</li>
                    <li>The CSV cannot change account roles.</li>
                    <li>Archived cohorts are rejected.</li>
                    <li>No assignments are changed during preview.</li>
                    <li>The preview expires after 30 minutes.</li>
                </ul>
            </section>

            <form
                method="POST"
                action="{{ route('admin.cohort-imports.preview') }}"
                enctype="multipart/form-data"
                class="rounded-3xl border-2 border-slate-200 bg-white p-6"
            >
                @csrf

                <label class="text-sm font-bold text-slate-700">
                    Cohort assignment CSV
                </label>

                <input
                    type="file"
                    name="csv"
                    accept=".csv,text/csv"
                    required
                    class="mt-2 block w-full rounded-xl border border-slate-300 p-3"
                >

                @error('csv')
                    <p class="mt-2 text-sm text-red-700">
                        {{ $message }}
                    </p>
                @enderror

                <button class="mt-5 rounded-xl bg-primary px-5 py-3 font-bold text-white">
                    Validate and Preview
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
