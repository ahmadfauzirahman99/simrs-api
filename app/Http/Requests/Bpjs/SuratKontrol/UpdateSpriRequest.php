<?php

namespace App\Http\Requests\Bpjs\SuratKontrol;

class UpdateSpriRequest extends BaseSuratKontrolRequest
{
    public function rules(): array
    {
        return array_merge($this->baseRules(), [
            'request.noSPRI' => ['required', 'string'],
        ]);
    }
}
