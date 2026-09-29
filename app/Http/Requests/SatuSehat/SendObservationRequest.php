<?php

namespace App\Http\Requests\SatuSehat;

use Illuminate\Foundation\Http\FormRequest;

class SendObservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_satu_sehat'  => ['required', 'string'],
            'patient_name'        => ['required', 'string'],
            'encounter_id'        => ['required', 'string'],
            'loinc_code'          => ['required', 'string'],
            'value'               => ['required', 'numeric'],
            'unit'                => ['required', 'string'],
            'category'            => ['nullable', 'string'],
            'effective_datetime'  => ['required', 'date'],
        ];
    }
}
