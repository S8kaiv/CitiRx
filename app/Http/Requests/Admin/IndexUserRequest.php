<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class IndexUserRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'q' => [
                'nullable',
                'string',
                'max:100',
            ],
            'role' => [
                'nullable',
                Rule::in([
                    'student',
                    'faculty',
                    'admin',
                ]),
            ],
            'cohort_id' => [
                'nullable',
                'uuid',
                'exists:cohorts,cohort_id',
            ],
            'verification' => [
                'nullable',
                Rule::in([
                    'verified',
                    'unverified',
                ]),
            ],
            'status' => [
                'nullable',
                Rule::in([
                    'active',
                    'deleted',
                    'all',
                ]),
            ],
            'no_cohort' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'q' => trim(
                (string) $this->input('q'),
            ),
        ]);
    }
}
