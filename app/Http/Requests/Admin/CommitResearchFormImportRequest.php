<?php

namespace App\Http\Requests\Admin;

class CommitResearchFormImportRequest extends AdminRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason' => trim(
                (string) $this->input('reason'),
            ),
        ]);
    }

    public function rules(): array
    {
        return [
            'selected_rows' => ['required', 'array', 'min:1', 'max:200'],
            'selected_rows.*' => ['required', 'integer', 'min:2', 'distinct'],
            'expected_lock_version' => [
                'required',
                'integer',
                'min:1',
            ],

            'reason' => [
                'nullable',
                'string',
                'max:500',
            ],

        ];
    }
}
