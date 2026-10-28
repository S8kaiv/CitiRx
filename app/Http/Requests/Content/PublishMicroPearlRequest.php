<?php

namespace App\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;

class PublishMicroPearlRequest extends FormRequest
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
            'published_at' => [
                'nullable',
                'date',
                'after_or_equal:now',
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
