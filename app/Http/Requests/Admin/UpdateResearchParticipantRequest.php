<?php

namespace App\Http\Requests\Admin;

use App\Models\ResearchParticipant;
use Illuminate\Validation\Rule;

class UpdateResearchParticipantRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in(ResearchParticipant::STATUSES),
            ],
            'expected_lock_version' => [
                'required',
                'integer',
                'min:0',
            ],
            'reason' => [
                'required',
                'string',
                'min:10',
                'max:500',
            ],
            'current_password' => [
                'required',
                'string',
                'current_password:web',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => trim(
                (string) $this->input('status'),
            ),
            'reason' => trim(
                (string) $this->input('reason'),
            ),
        ]);
    }
}
