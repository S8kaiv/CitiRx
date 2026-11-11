<?php

namespace App\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;

class PreviewMicroPearlImportRequest extends FormRequest
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
            'csv' => [
                'required',
                'file',
                'extensions:csv',
                'mimes:csv,txt',
                'max:2048',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'csv.extensions' => 'Upload a file with the .csv extension.',
            'csv.max' => 'The Micro Pearl CSV must not exceed 2 MB.',
        ];
    }
}
