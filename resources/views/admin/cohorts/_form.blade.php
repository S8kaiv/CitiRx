@php
    $editing = isset($cohort);

    $cohortName = old(
        'cohort_name',
        $editing ? $cohort->cohort_name : '',
    );

    $academicYear = old(
        'academic_year',
        $editing ? $cohort->academic_year : '',
    );

    $targetPhleDate = old(
        'target_phle_date',
        $editing
            ? $cohort->target_phle_date?->format('Y-m-d')
            : '',
    );
@endphp

<div class="space-y-5">
    <div>
        <label
            for="cohort_name"
            class="block text-sm font-bold text-slate-700"
        >
            Cohort Name
        </label>

        <input
            id="cohort_name"
            name="cohort_name"
            type="text"
            value="{{ $cohortName }}"
            maxlength="100"
            required
            autocomplete="off"
            placeholder="Example: BS Pharmacy Batch 2027"
            class="mt-1 block w-full rounded-xl border-slate-300
                focus:border-primary focus:ring-primary"
        >

        @error('cohort_name')
            <p class="mt-1 text-sm text-red-600">
                {{ $message }}
            </p>
        @enderror
    </div>

    <div>
        <label
            for="academic_year"
            class="block text-sm font-bold text-slate-700"
        >
            Academic Year
        </label>

        <input
            id="academic_year"
            name="academic_year"
            type="text"
            value="{{ $academicYear }}"
            maxlength="20"
            required
            inputmode="numeric"
            autocomplete="off"
            placeholder="2026-2027"
            class="mt-1 block w-full rounded-xl border-slate-300
                focus:border-primary focus:ring-primary"
        >

        <p class="mt-1 text-xs text-slate-500">
            Use the format YYYY-YYYY.
        </p>

        @error('academic_year')
            <p class="mt-1 text-sm text-red-600">
                {{ $message }}
            </p>
        @enderror
    </div>

    <div>
        <label
            for="target_phle_date"
            class="block text-sm font-bold text-slate-700"
        >
            Target PhLE Date
        </label>

        <input
            id="target_phle_date"
            name="target_phle_date"
            type="date"
            value="{{ $targetPhleDate }}"
            class="mt-1 block w-full rounded-xl border-slate-300
                focus:border-primary focus:ring-primary"
        >

        <p class="mt-1 text-xs text-slate-500">
            Optional. This is the expected licensure examination date.
        </p>

        @error('target_phle_date')
            <p class="mt-1 text-sm text-red-600">
                {{ $message }}
            </p>
        @enderror
    </div>

    <div class="flex flex-wrap gap-3 border-t border-slate-100 pt-5">
        <button
            type="submit"
            class="rounded-xl bg-primary px-5 py-2.5 text-sm
                font-bold text-white transition hover:opacity-90"
        >
            {{ $submitLabel }}
        </button>

        <a
            href="{{ $editing
                ? route('admin.cohorts.show', ['cohort' => $cohort])
                : route('admin.cohorts.index') }}"
            class="rounded-xl border-2 border-slate-200 bg-white
                px-5 py-2.5 text-sm font-bold text-slate-700
                transition hover:bg-slate-50"
        >
            Cancel
        </a>
    </div>
</div>
