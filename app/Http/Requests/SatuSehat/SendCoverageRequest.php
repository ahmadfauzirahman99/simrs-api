<?php

namespace App\Http\Requests\SatuSehat;

use Illuminate\Foundation\Http\FormRequest;

class SendCoverageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_satu_sehat' => ['required', 'string'],
            'nomor_kartu'        => ['required', 'string'],
            'kelas'              => ['nullable', 'in:1,2,3'],
            'ppu_type'           => ['nullable', 'string'],
            'ppu_name'           => ['nullable', 'string'],
        ];
    }
}
