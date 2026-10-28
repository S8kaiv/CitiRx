<?php

namespace App\Http\Requests\Admin;

use App\Models\PilotControl;
use Illuminate\Validation\Rule;

class UpdatePilotControlRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'phase' => [
                'required',
                Rule::in(PilotControl::PHASES),
            ],
            'lock_version' => [
                'required',
                'integer',
                'min:1',
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
            'emergency_override' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phase' => trim(
                (string) $this->input('phase'),
            ),
            'reason' => trim(
                (string) $this->input('reason'),
            ),
            'emergency_override' => $this->boolean(
                'emergency_override',
            ),
        ]);
    }
}
