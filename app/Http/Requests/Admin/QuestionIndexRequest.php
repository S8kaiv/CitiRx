<?php

namespace App\Http\Requests\Admin;

use App\Models\Question;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuestionIndexRequest extends FormRequest
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
            'form' => [
                'nullable',
                Rule::in([
                    Question::FORM_PRE_TEST_A,
                    Question::FORM_POST_TEST_B,
                ]),
            ],

            'domain_id' => [
                'nullable',
                'integer',
                'exists:tos_domains,domain_id',
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'validated',
                    'unvalidated',
                    'validation_issue',
                    'served',
                    'unserved',
                    'locked',
                    'editable',
                    'active',
                    'inactive',
                    'placeholder',
                ]),
            ],

            'q' => [
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'q' => trim(
                (string) $this->input(
                    'q',
                    '',
                ),
            ),
        ]);
    }
}
