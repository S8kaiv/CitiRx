<?php

namespace App\Http\Requests\Dean;

use Illuminate\Foundation\Http\FormRequest;

class ApproveResearchFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'dean';
    }

    public function rules(): array
    {
        return [
            'expected_lock_version' => ['required', 'integer', 'min:1'],
            'expected_digest' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'],
            'review_confirmed' => ['required', 'accepted'],
        ];
    }
}
