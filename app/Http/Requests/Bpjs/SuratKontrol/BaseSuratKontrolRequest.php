<?php

namespace App\Http\Requests\Bpjs\SuratKontrol;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseSuratKontrolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function baseRules(): array
    {
        return [
            'request' => ['required', 'array'],
            'request.kodeDokter' => ['required', 'string'],
            'request.poliKontrol' => ['required', 'string'],
            'request.tglRencanaKontrol' => ['required', 'date'],
            'request.user' => ['required', 'string'],
        ];
    }

    public function payload(): array
    {
        return $this->validated('request');
    }
}
