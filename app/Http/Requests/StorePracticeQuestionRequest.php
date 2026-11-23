<?php

namespace App\Http\Requests;

use App\Http\Requests\Admin\UpdateQuestionRequest;
use App\Models\Question;
use Illuminate\Validation\Rule;

class StorePracticeQuestionRequest extends UpdateQuestionRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['edit_version'], $rules['change_reason']);

        return [
            ...$rules,
            'competency_id' => ['required', 'uuid', 'exists:tos_competencies,competency_id'],
            'hypercorrection_rationale' => ['required', 'string', 'max:2000'],
            'source_reference' => ['required', 'string', 'max:2000'],
            'copyright_permission' => ['required', Rule::in(Question::SOURCE_RIGHTS_STATUSES)],
        ];
    }
}
