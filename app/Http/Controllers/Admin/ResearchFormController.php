<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ViewResearchFormsRequest;
use App\Services\ResearchFormAuditService;
use Illuminate\View\View;

class ResearchFormController extends Controller
{
    public function __construct(
        protected ResearchFormAuditService $audit,
    ) {}

    public function index(
        ViewResearchFormsRequest $request,
    ): View {
        $request->validated();

        return view(
            'admin.research-forms.index',
            [
                'reports' => $this->audit->auditAll(
                    requireValidation: true,
                ),

                'developmentOverrideConfigured' => (bool) config(
                    'citirx.research.'
                        .'allow_unvalidated_forms',
                    false,
                ),
            ],
        );
    }
}
