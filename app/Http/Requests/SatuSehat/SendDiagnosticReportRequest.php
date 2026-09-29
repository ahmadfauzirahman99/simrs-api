<?php

namespace App\Http\Requests\SatuSehat;

use Illuminate\Foundation\Http\FormRequest;

class SendDiagnosticReportRequest extends FormRequest
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
            'loinc_code'          => ['required', 'string'],
            'loinc_display'       => ['required', 'string'],
            'category'            => ['nullable', 'in:lab,radiology,pathology'],
            'status'              => ['nullable', 'in:registered,partial,preliminary,final,amended,corrected,appended,cancelled,entered-in-error,unknown'],
            'report_no'           => ['nullable', 'string'],
            'effective_datetime'  => ['nullable', 'date'],
            'issued'              => ['nullable', 'date'],
            'performers'          => ['nullable', 'array'],
            'performers.*'        => ['string'],
            'results'             => ['nullable', 'array'],
            'results.*'           => ['string'],
        ];
    }
}
