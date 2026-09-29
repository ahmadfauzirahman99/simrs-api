<?php

namespace App\Http\Requests\SatuSehat;

use Illuminate\Foundation\Http\FormRequest;

class SendMedicationDispenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_satu_sehat'                  => ['required', 'string'],
            'encounter_id'                        => ['required', 'string'],
            'kfa_code'                            => ['required', 'string'],
            'kfa_display'                         => ['required', 'string'],
            'dispensing_no'                       => ['nullable', 'string'],
            'medication_request_id'               => ['nullable', 'string'],
            'practitioner_id'                     => ['nullable', 'string'],
            'status'                              => ['nullable', 'in:preparation,in-progress,cancelled,on-hold,completed,entered-in-error,stopped,unknown'],
            'category'                            => ['nullable', 'in:inpatient,outpatient,community'],
            'quantity'                            => ['nullable', 'numeric', 'min:0'],
            'quantity_unit'                       => ['nullable', 'string'],
            'days_supply'                         => ['nullable', 'integer', 'min:1'],
            'when_handed_over'                    => ['nullable', 'date'],
            'dosage_instructions'                 => ['nullable', 'array'],
            'dosage_instructions.*.frequency'     => ['nullable', 'integer', 'min:1'],
            'dosage_instructions.*.period'        => ['nullable', 'integer', 'min:1'],
            'dosage_instructions.*.period_unit'   => ['nullable', 'in:s,min,h,d,wk,mo,a'],
            'dosage_instructions.*.dose_value'    => ['nullable', 'numeric', 'min:0'],
            'dosage_instructions.*.dose_unit'     => ['nullable', 'string'],
        ];
    }
}
