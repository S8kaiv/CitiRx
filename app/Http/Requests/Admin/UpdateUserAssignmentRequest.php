<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Validation\Rule;

class UpdateUserAssignmentRequest extends AdminRequest
{
    protected function prepareForValidation(): void
    {
        $reason = trim(
            (string) $this->input('reason'),
        );

        $this->merge([
            'cohort_id' => $this->input('cohort_id') ?: null,
            'reason' => $reason !== ''
                ? $reason
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'role' => [
                'required',
                Rule::in([
                    'student',
                    'faculty',
                    'admin',
                    'dean',
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
                Rule::requiredIf(
                    fn (): bool => $this->roleIsChanging(),
                ),
                'nullable',
                'string',
                'min:5',
                'max:500',
            ],
        ];
    }

    private function roleIsChanging(): bool
    {
        $currentRole = User::withTrashed()
            ->whereKey(
                (string) $this->route('userId'),
            )
            ->value('role');

        return is_string($currentRole)
            && $currentRole
                !== $this->input('role');
    }
}
