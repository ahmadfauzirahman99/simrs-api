<?php

namespace App\Http\Requests\SatuSehat;

use Illuminate\Foundation\Http\FormRequest;

class SendClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_satu_sehat'         => ['required', 'string'],
            'no_sep'                     => ['required', 'string'],
            'no_batch'                   => ['required', 'string'],
            'coverage_id'                => ['required', 'string'],
            'invoice_id'                 => ['required', 'string'],
            'encounter_id'               => ['required', 'string'],
            'start_date'                 => ['required', 'date'],
            'end_date'                   => ['required', 'date', 'after_or_equal:start_date'],
            'total'                      => ['required', 'integer', 'min:0'],
            'practitioner_id'            => ['nullable', 'string'],
            'practitioner_nik'           => ['nullable', 'string'],
            'location_id'                => ['nullable', 'string'],
            'inacbg_code'                => ['nullable', 'string'],
            'inacbg_display'             => ['nullable', 'string'],
            'inacbg_value'               => ['nullable', 'integer', 'min:0'],
            'tariff_class'               => ['nullable', 'string'],
            'tariff_class_display'       => ['nullable', 'string'],
            'claim_sub_type'             => ['nullable', 'string', 'in:drg,ffs'],
            'upgrade_class'              => ['nullable', 'string'],
            'upgrade_class_display'      => ['nullable', 'string'],
            'created_at'                 => ['nullable', 'date'],
            'diagnoses'                  => ['nullable', 'array'],
            'diagnoses.*.icd10_code'     => ['required_with:diagnoses', 'string'],
            'diagnoses.*.display'        => ['required_with:diagnoses', 'string'],
            'diagnoses.*.type'           => ['nullable', 'in:discharge,admitting'],
            'procedures'                 => ['nullable', 'array'],
            'procedures.*.icd9cm_code'   => ['required_with:procedures', 'string'],
            'procedures.*.display'       => ['required_with:procedures', 'string'],
        ];
    }
}
