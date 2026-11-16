<?php

namespace App\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;

class SubmitMicroPearlForReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array(
            $this->user()?->role,
            ['admin', 'dean', 'faculty'],
            true,
        );
    }

    protected function prepareForValidation(): void
    {
        $reason = trim((string) $this->input('reason'));

        $this->merge(['reason' => $reason !== '' ? $reason : null]);
    }

    public function rules(): array
    {
        return [
            'edit_version' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
