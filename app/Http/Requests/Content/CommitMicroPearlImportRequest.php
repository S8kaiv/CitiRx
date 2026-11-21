<?php

namespace App\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;

class CommitMicroPearlImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array(
            $this->user()?->role,
            ['admin', 'dean', 'faculty'],
            true,
        );
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
        ];
    }
}
