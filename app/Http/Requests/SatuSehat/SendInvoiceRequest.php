<?php

namespace App\Http\Requests\SatuSehat;

use Illuminate\Foundation\Http\FormRequest;

class SendInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_satu_sehat'         => ['required', 'string'],
            'account_id'                 => ['required', 'string'],
            'date'                       => ['required', 'date'],
            'total_net'                  => ['required', 'integer', 'min:0'],
            'line_items'                 => ['required', 'array', 'min:1'],
            'line_items.*.charge_item_id' => ['required', 'string'],
            'line_items.*.kptl_code'     => ['required', 'string'],
            'line_items.*.kptl_display'  => ['required', 'string'],
            'line_items.*.amount'        => ['required', 'integer', 'min:0'],
        ];
    }
}
