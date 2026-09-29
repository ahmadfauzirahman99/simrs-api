<?php

namespace App\Http\Requests\SatuSehat;

use Illuminate\Foundation\Http\FormRequest;

class SendImmunizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_satu_sehat'  => ['required', 'string'],
            'encounter_id'        => ['required', 'string'],
            'kfa_code'            => ['required', 'string'],
            'kfa_display'         => ['required', 'string'],
            'immunization_no'     => ['nullable', 'string'],
            'status'              => ['nullable', 'in:completed,entered-in-error,not-done'],
            'occurrence_datetime' => ['nullable', 'date'],
            'location_id'         => ['nullable', 'string'],
            'practitioner_id'     => ['nullable', 'string'],
            'lot_number'          => ['nullable', 'string'],
            'site_code'           => ['nullable', 'string'],
            'site_display'        => ['nullable', 'string'],
            'route_code'          => ['nullable', 'string'],
            'route_display'       => ['nullable', 'string'],
            'dose_value'          => ['nullable', 'numeric', 'min:0'],
            'dose_unit'           => ['nullable', 'string'],
        ];
    }
}
