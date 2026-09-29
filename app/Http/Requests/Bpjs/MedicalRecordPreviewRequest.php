<?php

namespace App\Http\Requests\Bpjs;

use Illuminate\Foundation\Http\FormRequest;

class MedicalRecordPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'no_sep' => ['required', 'string'],
            'jns_pelayanan' => ['required', 'in:1,2'],
            'bulan' => ['required', 'digits:2'],
            'tahun' => ['required', 'digits:4'],
            'bundle' => ['nullable', 'array'],
            'bundle.resourceType' => ['nullable', 'string', 'in:Bundle'],
            'bundle.type' => ['nullable', 'string'],
            'bundle.entry' => ['nullable', 'array'],
        ];
    }
}
