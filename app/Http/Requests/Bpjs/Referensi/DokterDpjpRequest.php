<?php

namespace App\Http\Requests\Bpjs\Referensi;

use Illuminate\Foundation\Http\FormRequest;

class DokterDpjpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kode' => ['required', 'string'],
            'jenis' => ['required', 'string'],
            'tgl' => ['required', 'date'],
        ];
    }
}
