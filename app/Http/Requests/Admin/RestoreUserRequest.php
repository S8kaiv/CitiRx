<?php

namespace App\Http\Requests\Admin;

class RestoreUserRequest extends AdminRequest
{
    protected function prepareForValidation(): void
    {
        $reason = trim(
            (string) $this->input('reason'),
        );

        $this->merge([
            'reason' => $reason !== ''
                ? $reason
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'reason' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }
}
