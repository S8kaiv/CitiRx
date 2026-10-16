<?php

namespace App\Http\Requests\Admin;

class AdminActionRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
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
            'reason' => trim(
                (string) $this->input('reason'),
            ),
        ]);
    }
}
