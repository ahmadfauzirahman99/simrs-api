<?php

namespace App\Http\Requests\Bpjs\SuratKontrol;

use Illuminate\Foundation\Http\FormRequest;

class SearchSepSuratKontrolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'no_sep' => ['required', 'string'],
        ];
    }
}
