<?php

namespace App\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;

class MicroPearlTransitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array(
            $this->user()?->role,
            ['admin', 'faculty'],
            true,
        );
    }

    public function rules(): array
    {
        return [
            'edit_version' => [
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
