<?php

namespace App\Http\Requests\SatuSehat;

use Illuminate\Foundation\Http\FormRequest;

class SendAllergyIntoleranceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_satu_sehat'                     => ['required', 'string'],
            'encounter_id'                           => ['required', 'string'],
            'snomed_code'                            => ['required', 'string'],
            'snomed_display'                         => ['required', 'string'],
            'category'                               => ['nullable', 'in:food,medication,environment,biologic'],
            'type'                                   => ['nullable', 'in:allergy,intolerance'],
            'criticality'                            => ['nullable', 'in:low,high,unable-to-assess'],
            'onset_datetime'                         => ['nullable', 'date'],
            'recorded_date'                          => ['nullable', 'date'],
            'reactions'                              => ['nullable', 'array'],
            'reactions.*.substance_code'             => ['required_with:reactions', 'string'],
            'reactions.*.substance_display'          => ['required_with:reactions', 'string'],
            'reactions.*.manifestation_code'         => ['required_with:reactions', 'string'],
            'reactions.*.manifestation_display'      => ['required_with:reactions', 'string'],
            'reactions.*.severity'                   => ['nullable', 'in:mild,moderate,severe'],
        ];
    }
}
