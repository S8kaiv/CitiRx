<?php

namespace App\Http\Requests\Pearl;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class IndexMicroPearlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $search = $this->input('q');

        if (is_string($search)) {
            $this->merge([
                'q' => Str::squish($search),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'q' => [
                'nullable',
                'string',
                'max:100',
            ],

            'domain_id' => [
                'nullable',
                'integer',
                Rule::exists(
                    'tos_domains',
                    'domain_id'
                ),
            ],

            'competency_id' => [
                'nullable',
                'uuid',

                Rule::exists(
                    'tos_competencies',
                    'competency_id'
                )->where(
                    function (
                        QueryBuilder $query
                    ): void {
                        if (
                            $this->filled(
                                'domain_id'
                            )
                        ) {
                            $query->where(
                                'domain_id',
                                $this->integer(
                                    'domain_id'
                                )
                            );
                        }
                    }
                ),
            ],
        ];
    }
}
