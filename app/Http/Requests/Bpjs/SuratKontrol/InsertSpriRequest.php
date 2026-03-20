<?php

namespace App\Http\Requests\Bpjs\SuratKontrol;

class InsertSpriRequest extends BaseSuratKontrolRequest
{
    public function rules(): array
    {
        return array_merge($this->baseRules(), [
            'request.noKartu' => ['required', 'string'],
        ]);
    }
}
