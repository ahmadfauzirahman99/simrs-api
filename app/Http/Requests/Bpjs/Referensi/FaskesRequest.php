<?php

namespace App\Http\Requests\Bpjs\Referensi;

use Illuminate\Foundation\Http\FormRequest;

class FaskesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keyword' => ['required', 'string'],
            'tipe' => ['nullable', 'in:1,2'],
        ];
    }
}
