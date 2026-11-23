<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommitPracticeQuestionImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['admin', 'dean'], true);
    }

    public function rules(): array
    {
        return [
            'selected_rows' => ['required', 'array', 'min:1', 'max:200'],
            'selected_rows.*' => ['required', 'integer', 'min:2', 'distinct'],
        ];
    }
}
