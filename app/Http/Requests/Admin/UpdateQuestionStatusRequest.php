<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuestionStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['admin', 'dean'], true);
    }

    protected function prepareForValidation(): void
    {
        $reason = trim(
            (string) $this->input('change_reason'),
        );

        $this->merge([
            'change_reason' => $reason !== ''
                ? $reason
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'active' => [
                'required',
                'boolean',
            ],
            'edit_version' => [
                'required',
                'integer',
                'min:1',
            ],
            'change_reason' => [
                Rule::requiredIf(
                    fn (): bool => ! $this->boolean('active'),
                ),
                'nullable',
                'string',
                'min:5',
                'max:500',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'change_reason' => 'reason for the status change',
        ];
    }
}
