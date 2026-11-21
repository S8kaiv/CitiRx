<?php

namespace App\Http\Requests\Dean;

use Illuminate\Foundation\Http\FormRequest;

class ReturnResearchFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'dean';
    }

    public function rules(): array
    {
        return [
            'expected_lock_version' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }
}
