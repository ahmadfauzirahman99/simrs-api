<?php

namespace App\Http\Requests\Bpjs\Rujukan;

use Illuminate\Foundation\Http\FormRequest;

class DetailRujukanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'no_rujukan' => ['required', 'string'],
            'faskes'     => ['nullable', 'in:1,2'],
        ];
    }
}
