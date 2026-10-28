<?php

namespace App\Http\Requests\Admin;

use App\Models\ResearchFormImport;
use Illuminate\Validation\Rule;

class PreviewResearchFormImportRequest extends AdminRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'import_type' => mb_strtolower(
                trim(
                    (string) $this->input(
                        'import_type',
                    ),
                ),
            ),

            'validator_id' => filled(
                $this->input('validator_id'),
            )
                ? trim(
                    (string) $this->input(
                        'validator_id',
                    ),
                )
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'import_type' => [
                'required',
                Rule::in(
                    ResearchFormImport::TYPES,
                ),
            ],

            'form_version_id' => [
                'required',
                'uuid',
                Rule::exists(
                    'research_form_versions',
                    'form_version_id',
                ),
            ],

            'validator_id' => [
                'nullable',
                'required_if:import_type,validation',
                'uuid',
                Rule::exists(
                    'users',
                    'user_id',
                ),
            ],

            'csv' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:2048',
            ],
        ];
    }
}
