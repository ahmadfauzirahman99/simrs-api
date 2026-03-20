<?php

namespace App\Http\Requests\Bpjs;

use Illuminate\Foundation\Http\FormRequest;

class SearchSepRequest extends FormRequest
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
