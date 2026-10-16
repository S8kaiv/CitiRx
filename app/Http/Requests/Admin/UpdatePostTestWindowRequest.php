<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdatePostTestWindowRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'clear_window' => [
                'nullable',
                'boolean',
            ],
            'post_test_opens_at' => [
                Rule::requiredIf(
                    ! $this->boolean('clear_window'),
                ),
                'nullable',
                'date',
            ],
            'post_test_closes_at' => [
                Rule::requiredIf(
                    ! $this->boolean('clear_window'),
                ),
                'nullable',
                'date',
                'after:post_test_opens_at',
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
            'post_test_opens_at' => $this->input(
                'post_test_opens_at',
            ) ?: null,
            'post_test_closes_at' => $this->input(
                'post_test_closes_at',
            ) ?: null,
            'reason' => trim(
                (string) $this->input('reason'),
            ),
        ]);
    }
}
