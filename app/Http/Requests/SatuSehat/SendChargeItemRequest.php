<?php

namespace App\Http\Requests\SatuSehat;

use Illuminate\Foundation\Http\FormRequest;

class SendChargeItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_satu_sehat' => ['required', 'string'],
            'encounter_id'       => ['required', 'string'],
            'account_id'         => ['required', 'string'],
            'kptl_code'          => ['required', 'string'],
            'kptl_display'       => ['required', 'string'],
            'unit_price'         => ['required', 'integer', 'min:0'],
            'total_price'        => ['nullable', 'integer', 'min:0'],
            'quantity'           => ['nullable', 'integer', 'min:1'],
            'practitioner_id'    => ['nullable', 'string'],
            'snomed_code'        => ['nullable', 'string'],
            'snomed_display'     => ['nullable', 'string'],
            'start_date'         => ['nullable', 'date'],
            'end_date'           => ['nullable', 'date'],
        ];
    }
}
