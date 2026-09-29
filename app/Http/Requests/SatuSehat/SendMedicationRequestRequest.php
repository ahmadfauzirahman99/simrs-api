<?php

namespace App\Http\Requests\SatuSehat;

use Illuminate\Foundation\Http\FormRequest;

class SendMedicationRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_satu_sehat'                       => ['required', 'string'],
            'encounter_id'                             => ['required', 'string'],
            'kfa_code'                                 => ['required', 'string'],
            'kfa_display'                              => ['required', 'string'],
            'prescription_no'                          => ['nullable', 'string'],
            'practitioner_id'                          => ['nullable', 'string'],
            'category'                                 => ['nullable', 'in:inpatient,outpatient,community'],
            'authored_on'                              => ['nullable', 'date'],
            'dosage_instructions'                      => ['nullable', 'array'],
            'dosage_instructions.*.frequency'          => ['nullable', 'integer', 'min:1'],
            'dosage_instructions.*.period'             => ['nullable', 'integer', 'min:1'],
            'dosage_instructions.*.period_unit'        => ['nullable', 'in:s,min,h,d,wk,mo,a'],
            'dosage_instructions.*.dose_value'         => ['nullable', 'numeric', 'min:0'],
            'dosage_instructions.*.dose_unit'          => ['nullable', 'string'],
            'dosage_instructions.*.route_code'         => ['nullable', 'string'],
            'dosage_instructions.*.route_display'      => ['nullable', 'string'],
            'dispense_quantity'                        => ['nullable', 'integer', 'min:1'],
            'dispense_unit'                            => ['nullable', 'string'],
            'days_supply'                              => ['nullable', 'integer', 'min:1'],
        ];
    }
}
