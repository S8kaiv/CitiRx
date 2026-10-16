<?php

namespace App\Http\Requests\Admin;

use App\Models\Cohort;
use Illuminate\Validation\Rule;

class UpdateCohortRequest extends AdminRequest
{
    public function rules(): array
    {
        /** @var Cohort $cohort */
        $cohort = $this->route('cohort');

        return [
            'cohort_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique(
                    'cohorts',
                    'cohort_name',
                )
                    ->where(
                        fn ($query) => $query->where(
                            'academic_year',
                            $this->input('academic_year'),
                        ),
                    )
                    ->ignore(
                        $cohort->cohort_id,
                        'cohort_id',
                    ),
            ],
            'academic_year' => [
                'required',
                'string',
                'regex:/^\d{4}-\d{4}$/',
                'max:20',
            ],
            'target_phle_date' => [
                'nullable',
                'date',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cohort_name' => trim(
                (string) $this->input('cohort_name'),
            ),
            'academic_year' => trim(
                (string) $this->input('academic_year'),
            ),
            'target_phle_date' => $this->input(
                'target_phle_date',
            ) ?: null,
        ]);
    }
}
