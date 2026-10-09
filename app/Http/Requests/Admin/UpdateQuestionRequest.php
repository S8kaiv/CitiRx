<?php

namespace App\Http\Requests\Admin;

use App\Models\Question;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuestionRequest extends FormRequest
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
        $question = $this->route(
            'question',
        );

        $isResearchQuestion =
            $question instanceof Question
            && $question->research_form
                !== null;

        return [
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

            'question_text' => [
                'required',
                'string',
                'max:2000',
            ],

            'hypercorrection_rationale' => [
                $isResearchQuestion
                    ? 'required'
                    : 'nullable',

                'string',
                'max:2000',
            ],

            'cognitive_level' => [
                $isResearchQuestion
                    ? 'required'
                    : 'nullable',

                Rule::in(
                    Question::COGNITIVE_LEVELS,
                ),
            ],

            /*
             * The array:A,B,C,D rule rejects extra keys.
             */
            'choices' => [
                'required',
                'array:A,B,C,D',
                'size:4',
            ],

            'choices.A.text' => [
                'required',
                'string',
                'max:1000',
            ],

            'choices.B.text' => [
                'required',
                'string',
                'max:1000',
            ],

            'choices.C.text' => [
                'required',
                'string',
                'max:1000',
            ],

            'choices.D.text' => [
                'required',
                'string',
                'max:1000',
            ],

            'correct_choice' => [
                'required',
                Rule::in([
                    'A',
                    'B',
                    'C',
                    'D',
                ]),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'change_reason' => 'reason for the change',

            'hypercorrection_rationale' => 'rationale',

            'cognitive_level' => 'cognitive level',

            'choices.A.text' => 'choice A',

            'choices.B.text' => 'choice B',

            'choices.C.text' => 'choice C',

            'choices.D.text' => 'choice D',

            'correct_choice' => 'correct answer',
        ];
    }
}
