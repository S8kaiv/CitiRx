<?php

namespace App\Http\Requests\Admin;

class TransitionResearchFormVersionRequest extends AdminRequest
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
            'expected_lock_version' => [
                'required',
                'integer',
                'min:1',
            ],

            'reason' => [
                'required',
                'string',
                'min:10',
                'max:500',
            ],
        ];
    }
}
