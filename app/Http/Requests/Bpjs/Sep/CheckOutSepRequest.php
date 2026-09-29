<?php

namespace App\Http\Requests\Bpjs\Sep;

class CheckOutSepRequest extends BaseSepRequest
{
    public function rules(): array
    {
        return array_merge($this->baseRules(), [
            'request.t_sep.noSep'     => ['required', 'string'],
            'request.t_sep.tglPulang' => ['required', 'date'],
            'request.t_sep.user'      => ['required', 'string'],
        ]);
    }
}
