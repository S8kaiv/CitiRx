<?php

namespace App\Http\Requests\Admin;

class CommitCohortAssignmentImportRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'lock_version' => [
                'required',
                'integer',
                'min:1',
            ],
            'reason' => [
                'required',
                'string',
                'min:5',
                'max:500',
            ],
            'current_password' => [
                'required',
                'current_password',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason' => trim(
                (string) $this->input('reason'),
            ),
        ]);
    }
}
