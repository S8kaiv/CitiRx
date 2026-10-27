<?php

namespace App\Http\Requests\Admin;

class PreviewCohortAssignmentImportRequest extends AdminRequest
{
    public function rules(): array
    {
        return [
            'csv' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:2048',
            ],
        ];
    }
}
