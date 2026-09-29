<?php

namespace App\Http\Requests\Bpjs\SuratKontrol;

use Illuminate\Foundation\Http\FormRequest;

class DeleteSuratKontrolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'request'              => ['required', 'array'],
            'request.noSuratKontrol' => ['required', 'string'],
            'request.user'         => ['required', 'string'],
        ];
    }

    public function payload(): array
    {
        return $this->validated('request');
    }
}
