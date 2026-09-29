<?php

namespace App\Http\Requests\Bpjs\Sep;

class ApprovalSepRequest extends BaseSepRequest
{
    public function rules(): array
    {
        return array_merge($this->baseRules(), [
            'request.t_sep.noSep'    => ['required', 'string'],
            'request.t_sep.statusApproval' => ['required', 'in:1,2'],
            'request.t_sep.user'     => ['required', 'string'],
        ]);
    }
}
