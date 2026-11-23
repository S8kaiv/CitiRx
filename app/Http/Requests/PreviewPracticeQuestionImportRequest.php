<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PreviewPracticeQuestionImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['admin', 'dean'], true);
    }

    public function rules(): array
    {
        return ['csv' => ['required', 'file', 'extensions:csv', 'mimes:csv,txt', 'max:2048']];
    }
}
