<?php

namespace App\Http\Requests\Admin;

use App\Models\Cohort;
use App\Models\User;
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
                Rule::in([
                    User::class,
                    Cohort::class,
                ]),
            ],
        ];
    }
}
