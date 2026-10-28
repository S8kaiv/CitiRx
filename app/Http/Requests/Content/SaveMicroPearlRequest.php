<?php

namespace App\Http\Requests\Content;

use App\Models\MicroPearl;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveMicroPearlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array(
            $this->user()?->role,
            ['admin', 'faculty'],
            true,
        );
    }

    public function rules(): array
    {
        $pearl = $this->route('pearl');

        $requiresGenerationMetadata = $this->boolean('is_ai_generated');

        $orderRule = Rule::unique(
            'micro_pearls',
            'order_index',
        )->where(
            fn (QueryBuilder $query) => $query->where(
                'competency_id',
                $this->input('competency_id'),
            ),
        );

        if ($pearl instanceof MicroPearl) {
            $orderRule->ignore(
                $pearl->pearl_id,
                'pearl_id',
            );
        }

        return [
            'competency_id' => [
                'required',
                'uuid',
                'exists:tos_competencies,competency_id',
            ],
            'title' => [
                'required',
                'string',
                'max:200',
            ],
            'mnemonic_summary' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'lesson_content' => [
                'nullable',
                'string',
                'max:30000',
            ],
            'learning_objectives_text' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'key_points_text' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'common_pitfalls_text' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'recall_prompts_text' => [
                'nullable',
                'string',
                'max:10000',
            ],
            'source_references_text' => [
                'nullable',
                'string',
                'max:10000',
            ],
            'estimated_read_seconds' => [
                'required',
                'integer',
                'min:30',
                'max:3600',
            ],
            'order_index' => [
                'required',
                'integer',
                'min:1',
                'max:65535',
                $orderRule,
            ],
            'rights_status' => [
                'required',
                Rule::in([
                    MicroPearl::RIGHTS_PENDING,
                    MicroPearl::RIGHTS_ORIGINAL,
                    MicroPearl::RIGHTS_AUTHORIZED,
                    MicroPearl::RIGHTS_PUBLIC_DOMAIN,
                ]),
            ],
            'is_ai_generated' => [
                'required',
                'boolean',
            ],
            'ai_enrichment_allowed' => [
                'required',
                'boolean',
            ],
            'generation_provider' => [
                'nullable',
                Rule::requiredIf(
                    $requiresGenerationMetadata,
                ),
                'string',
                'max:100',
            ],
            'generation_model' => [
                'nullable',
                Rule::requiredIf(
                    $requiresGenerationMetadata,
                ),
                'string',
                'max:100',
            ],
            'prompt_version' => [
                'nullable',
                Rule::requiredIf(
                    $requiresGenerationMetadata,
                ),
                'string',
                'max:100',
            ],
            'edit_version' => [
                $pearl instanceof MicroPearl
                    ? 'required'
                    : 'nullable',
                'integer',
                'min:1',
            ],
            'reason' => [
                $pearl instanceof MicroPearl
                    ? 'required'
                    : 'nullable',
                'string',
                'min:5',
                'max:500',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_ai_generated' => $this->boolean(
                'is_ai_generated',
            ),
            'ai_enrichment_allowed' => $this->boolean(
                'ai_enrichment_allowed',
            ),
            'reason' => trim(
                (string) $this->input('reason'),
            ),
        ]);
    }
}
