<?php

namespace App\Http\Requests\Dean;

use App\Http\Requests\Admin\StoreCohortRequest as AdminStoreCohortRequest;

class StoreCohortRequest extends AdminStoreCohortRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'dean';
    }
}
