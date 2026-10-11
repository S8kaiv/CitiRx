<?php

namespace App\Http\Requests;

use App\Models\FacultyIntervention;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFacultyInterventionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role
                === 'faculty'
            && $this->user()?->cohort_id
                !== null;
    }

    public function rules(): array
    {
        $cohortId =
            $this->user()?->cohort_id;

        $domainId =
            $this->input('domain_id');

        return [
            'target_user_id' => [
                'required',
                'uuid',

                Rule::exists(
                    'users',
                    'user_id',
                )->where(
                    fn ($query) => $query
                        ->where(
                            'role',
                            'student',
                        )
                        ->where(
                            'cohort_id',
                            $cohortId,
                        )
                        ->whereNull(
                            'deleted_at',
                        ),
                ),
            ],

            'domain_id' => [
                'required',
                'integer',
                Rule::exists(
                    'tos_domains',
                    'domain_id',
                ),
            ],

            'competency_id' => [
                'nullable',
                'uuid',

                Rule::exists(
                    'tos_competencies',
                    'competency_id',
                )->where(
                    fn ($query) => $query->where(
                        'domain_id',
                        $domainId,
                    ),
                ),
            ],

            'intervention_type' => [
                'required',

                Rule::in(
                    array_keys(
                        FacultyIntervention::TYPE_LABELS,
                    ),
                ),
            ],

            'custom_message' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'target_user_id.exists' => 'The selected student is not available in your cohort.',

            'competency_id.exists' => 'The selected competency does not belong to the selected domain.',
        ];
    }
}
