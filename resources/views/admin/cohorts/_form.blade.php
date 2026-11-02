@php
    $editing = isset($cohort);

    $cohortName = old(
        'cohort_name',
        $editing
            ? $cohort->cohort_name
            : '',
    );

    $academicYear = old(
        'academic_year',
        $editing
            ? $cohort->academic_year
            : '',
    );

    $targetPhleDate = old(
        'target_phle_date',
        $editing
            ? $cohort->target_phle_date?->format('Y-m-d')
            : '',
    );
@endphp

<div class="space-y-5">
    <x-ui.field
        name="cohort_name"
        label="Cohort name"
        :value="$cohortName"
        maxlength="100"
        autocomplete="off"
        placeholder="Example: BS Pharmacy Batch 2027"
        required
    />

    <x-ui.field
        name="academic_year"
        label="Academic year"
        :value="$academicYear"
        maxlength="20"
        inputmode="numeric"
        autocomplete="off"
        placeholder="2026-2027"
        hint="Use the format YYYY-YYYY."
        required
    />

    <x-ui.field
        name="target_phle_date"
        label="Target PhLE date"
        type="date"
        :value="$targetPhleDate"
        hint="Optional. This is the expected licensure examination date."
    />

    <div class="flex flex-wrap gap-3 border-t border-line pt-5">
        <x-ui.button type="submit">
            {{ $submitLabel }}
        </x-ui.button>

        <x-ui.button
            variant="secondary"
            :href="$editing
                ? route('admin.cohorts.show', [
                    'cohort' => $cohort,
                ])
                : route('admin.cohorts.index')"
        >
            Cancel
        </x-ui.button>
    </div>
</div>
