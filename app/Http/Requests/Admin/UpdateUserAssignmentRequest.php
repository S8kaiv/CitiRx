<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateUserAssignmentRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'role' => [
                'required',
                Rule::in([
                    'student',
                    'faculty',
                    'admin',
                ]),
            ],
            'cohort_id' => [
                'nullable',
                'uuid',
                Rule::exists(
                    'cohorts',
                    'cohort_id',
                )->where(
                    fn ($query) => $query
                        ->whereNull('archived_at'),
                ),
            ],
            'reason' => [
                'required',
                'string',
                'min:5',
                'max:500',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cohort_id' => $this->input('cohort_id') ?: null,
            'reason' => trim(
                (string) $this->input('reason'),
            ),
        ]);
    }
}
