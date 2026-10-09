<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestionStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role
            === 'admin';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'active' => [
                'required',
                'boolean',
            ],

            'edit_version' => [
                'required',
                'integer',
                'min:1',
            ],

            'change_reason' => [
                'required',
                'string',
                'min:5',
                'max:500',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'change_reason' => 'reason for the status change',
        ];
    }
}
