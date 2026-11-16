<?php

namespace App\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;

class PublishMicroPearlRequest extends FormRequest
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
            'current_password' => [
                'required',
                'string',
                'current_password',
            ],
        ];
    }
}
