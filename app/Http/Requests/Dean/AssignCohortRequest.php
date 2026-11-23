<?php

namespace App\Http\Requests\Dean;

use Illuminate\Foundation\Http\FormRequest;

class AssignCohortRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'dean';
    }

    public function rules(): array
    {
        return [
            'student_ids' => ['required', 'array', 'min:1', 'max:100'],
            'student_ids.*' => ['required', 'uuid', 'distinct'],
            'cohort_id' => ['required', 'uuid', 'exists:cohorts,cohort_id'],
            'original_cohorts' => ['required', 'array'],
            'original_cohorts.*' => ['nullable', 'uuid'],
        ];
    }
}
