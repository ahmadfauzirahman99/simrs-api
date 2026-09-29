<?php

namespace App\Http\Requests\SatuSehat;

use Illuminate\Foundation\Http\FormRequest;

class SendConditionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_satu_sehat' => ['required', 'string'],
            'patient_name'       => ['required', 'string'],
            'encounter_id'       => ['required', 'string'],
            'icd10_code'         => ['required', 'string'],
            'icd10_display'      => ['required', 'string'],
            'category'           => ['nullable', 'string', 'in:diagnosis,keluhan'],
            'onset_datetime'     => ['nullable', 'date'],
        ];
    }
}
