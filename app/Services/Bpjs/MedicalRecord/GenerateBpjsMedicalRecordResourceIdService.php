<?php

namespace App\Services\Bpjs\MedicalRecord;

use Illuminate\Support\Str;

class GenerateBpjsMedicalRecordResourceIdService
{
    public function generate(
        string $jnsPelayanan,
        ?string $uuid = null,
        ?string $rsCode = null,
        ?string $kemenkesCode = null,
    ): string {
        $rsCode = $this->sanitizeCode(
            $rsCode
                ?: (string) config('services.bpjs_medical_record.rs_code')
                ?: (string) config('services.bpjs_medical_record.provider_code')
        );

        $kemenkesCode = $this->sanitizeCode(
            $kemenkesCode
                ?: (string) config('services.bpjs_medical_record.kemenkes_code')
                ?: $rsCode
        );

        $jnsPelayanan = $this->sanitizeCode($jnsPelayanan);
        $uuid = $uuid ?: (string) Str::uuid();

        return $rsCode . $kemenkesCode . $jnsPelayanan . $uuid;
    }

    protected function sanitizeCode(?string $value): string
    {
        return preg_replace('/[^A-Za-z0-9]/', '', (string) $value) ?? '';
    }
}
