<?php

namespace App\Http\Requests\SatuSehat;

use Illuminate\Foundation\Http\FormRequest;

class SendEncounterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reg_id' => ['required', 'string'],
            'consultation_method' => ['required', 'string'],
            'arrived' => ['required', 'date'],
            'inprogress' => ['nullable', 'date'],
            'finished' => ['nullable', 'date'],
            'patient_satu_sehat' => ['required', 'string'],
            'patient_name' => ['required', 'string'],
            'doctor_satu_sehat' => ['required', 'string'],
            'unit_name' => ['required', 'string'],
            'location_id' => ['required', 'string'],
            'location_name' => ['required', 'string'],
        ];
    }
}
