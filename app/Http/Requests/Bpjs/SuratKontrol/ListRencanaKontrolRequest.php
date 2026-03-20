<?php

namespace App\Http\Requests\Bpjs\SuratKontrol;

use Illuminate\Foundation\Http\FormRequest;

class ListRencanaKontrolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bulan' => ['required', 'digits:2'],
            'tahun' => ['required', 'digits:4'],
            'no_kartu' => ['required', 'string'],
            'filter' => ['required', 'in:1,2'],
        ];
    }
}
