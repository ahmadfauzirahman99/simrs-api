<?php

namespace App\Http\Requests\Bpjs\Sep;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseSepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function baseRules(): array
    {
        return [
            'request' => ['required', 'array'],
            'request.t_sep' => ['required', 'array'],
        ];
    }

    public function payload(): array
    {
        return $this->validated();
    }
}
