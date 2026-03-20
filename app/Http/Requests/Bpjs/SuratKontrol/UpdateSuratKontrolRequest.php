<?php

namespace App\Http\Requests\Bpjs\SuratKontrol;

class UpdateSuratKontrolRequest extends BaseSuratKontrolRequest
{
    public function rules(): array
    {
        return array_merge($this->baseRules(), [
            'request.noSuratKontrol' => ['required', 'string'],
            'request.noSEP' => ['required', 'string'],
        ]);
    }
}
