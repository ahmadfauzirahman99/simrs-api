<?php

namespace App\Http\Requests\Bpjs\SuratKontrol;

use Illuminate\Foundation\Http\FormRequest;

class ListRencanaKontrolTanggalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tgl_awal' => ['required', 'date'],
            'tgl_akhir' => ['required', 'date'],
            'filter' => ['required', 'in:1,2'],
        ];
    }
}
