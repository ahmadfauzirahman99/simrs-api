<?php

namespace App\Http\Requests\SatuSehat;

use Illuminate\Foundation\Http\FormRequest;

class SendServiceRequestRequest extends FormRequest
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
            'snomed_code'         => ['required', 'string'],
            'snomed_display'      => ['required', 'string'],
            'practitioner_id'     => ['required', 'string'],
            'practitioner_name'   => ['required', 'string'],
            'priority'            => ['nullable', 'string', 'in:routine,urgent,asap,stat'],
            'occurrence_datetime' => ['nullable', 'date'],
            'condition_id'        => ['nullable', 'string'],
        ];
    }
}
