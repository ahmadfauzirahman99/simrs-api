<?php

namespace App\Http\Requests\Bpjs\Sep;

class DeleteSepRequest extends BaseSepRequest
{
    public function rules(): array
    {
        return array_merge($this->baseRules(), [
            'request.t_sep.noSep' => ['required', 'string'],
            'request.t_sep.user' => ['required', 'string'],
        ]);
    }
}
