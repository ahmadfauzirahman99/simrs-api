<?php

namespace App\Http\Requests\Bpjs\Rujukan;

use Illuminate\Foundation\Http\FormRequest;

class JumlahSepRujukanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'no_rujukan' => ['required', 'string'],
            'jenis'      => ['nullable', 'in:1,2'],
        ];
    }
}
