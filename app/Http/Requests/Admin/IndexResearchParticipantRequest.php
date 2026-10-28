<?php

namespace App\Http\Requests\Admin;

use App\Models\ResearchParticipant;
use Illuminate\Validation\Rule;

class IndexResearchParticipantRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'q' => [
                'nullable',
                'string',
                'max:100',
            ],
            'status' => [
                'nullable',
                Rule::in([
                    ...ResearchParticipant::STATUSES,
                    ResearchParticipant::STATUS_UNREGISTERED,
                ]),
            ],
            'cohort_id' => [
                'nullable',
                'uuid',
                'exists:cohorts,cohort_id',
            ],
            'account_status' => [
                'nullable',
                Rule::in([
                    'active',
                    'deleted',
                    'all',
                ]),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'q' => trim((string) $this->input('q')),
        ]);
    }
}
