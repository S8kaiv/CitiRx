<?php

namespace App\Http\Requests\Admin;

use App\Services\AdminAuditLogQueryService;
use Illuminate\Validation\Rule;

class IndexAuditLogRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'action' => [
                'nullable',
                'string',
                'max:80',
            ],
            'admin_id' => [
                'nullable',
                'uuid',
                'exists:users,user_id',
            ],
            'target_type' => [
                'nullable',
                Rule::in(
                    array_keys(
                        AdminAuditLogQueryService::TARGET_TYPES,
                    ),
                ),
            ],
            'target_id' => [
                'nullable',
                'uuid',
            ],
            'date_from' => [
                'nullable',
                'date_format:Y-m-d',
            ],
            'date_to' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:date_from',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'action' => $this->trimmedOrNull('action'),
            'admin_id' => $this->trimmedOrNull('admin_id'),
            'target_type' => $this->trimmedOrNull('target_type'),
            'target_id' => $this->trimmedOrNull('target_id'),
            'date_from' => $this->trimmedOrNull('date_from'),
            'date_to' => $this->trimmedOrNull('date_to'),
        ]);
    }

    private function trimmedOrNull(string $key): ?string
    {
        $value = trim((string) $this->input($key));

        return $value !== '' ? $value : null;
    }
}
