<?php

namespace App\Http\Requests\Bpjs\SuratKontrol;

class InsertSuratKontrolRequest extends BaseSuratKontrolRequest
{
    public function rules(): array
    {
        return array_merge($this->baseRules(), [
            'request.noSEP' => ['required', 'string'],
        ]);
    }
}
