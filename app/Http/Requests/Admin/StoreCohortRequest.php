<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class StoreCohortRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'cohort_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique(
                    'cohorts',
                    'cohort_name',
                )->where(
                    fn ($query) => $query->where(
                        'academic_year',
                        $this->input('academic_year'),
                    ),
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
