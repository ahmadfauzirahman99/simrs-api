<?php

namespace App\Http\Requests\Bpjs\Sep;

class InsertSepRequest extends BaseSepRequest
{
    public function rules(): array
    {
        return array_merge($this->baseRules(), [
            'request.t_sep.noKartu' => ['required', 'string'],
            'request.t_sep.tglSep' => ['required', 'date'],
            'request.t_sep.ppkPelayanan' => ['required', 'string'],
            'request.t_sep.jnsPelayanan' => ['required', 'string'],
            'request.t_sep.noMR' => ['required', 'string'],
            'request.t_sep.diagAwal' => ['required', 'string'],
            'request.t_sep.poli' => ['required', 'array'],
            'request.t_sep.poli.tujuan' => ['required', 'string'],
            'request.t_sep.catatan' => ['nullable', 'string'],
        ]);
    }
}
