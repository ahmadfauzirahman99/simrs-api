<?php

namespace App\Http\Requests\Bpjs;

use Illuminate\Foundation\Http\FormRequest;

class MonitoringKlaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date'],
            'tipe' => ['required', 'string'],
            'status' => ['required', 'string'],
        ];
    }
}
