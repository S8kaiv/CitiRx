<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class CopyResearchFormVersionRequest extends AdminRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'version_code' => mb_strtolower(
                trim(
                    (string) $this->input(
                        'version_code',
                    ),
                ),
            ),

            'reason' => trim(
                (string) $this->input('reason'),
            ),
        ]);
    }

    public function rules(): array
    {
        return [
            'version_code' => [
                'required',
                'string',
                'max:80',
                'regex:/^[a-z0-9_]+_v[1-9][0-9]*$/',
                Rule::unique(
                    'research_form_versions',
                    'version_code',
                ),
            ],

            'expected_lock_version' => [
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
        ];
    }
}
