<?php

namespace App\Http\Requests\Bpjs;

use Illuminate\Foundation\Http\FormRequest;

class PesertaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nomor' => ['required', 'string'],
            'tipe' => ['nullable', 'in:1,2'],
        ];
    }
}
