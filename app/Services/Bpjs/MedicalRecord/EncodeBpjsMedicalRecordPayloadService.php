<?php

namespace App\Services\Bpjs\MedicalRecord;

use InvalidArgumentException;

class EncodeBpjsMedicalRecordPayloadService
{
    public function gzipJson(array $bundle): string
    {
        $json = json_encode($bundle, JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new InvalidArgumentException('Gagal mengubah bundle medical record menjadi JSON.');
        }

        $compressed = gzcompress($json, 9);

        if ($compressed === false) {
            throw new InvalidArgumentException('Gagal melakukan kompresi gzip pada bundle medical record.');
        }

        return $compressed;
    }

    public function encryptCompressedPayload(string $compressed, ?string $consId = null, ?string $secretKey = null, ?string $providerCode = null): string
    {
        $consId = $consId ?: (string) config('services.bpjs_medical_record.cons_id', config('services.bpjs_v3.cons_id'));
        $secretKey = $secretKey ?: (string) config('services.bpjs_medical_record.secret_key', config('services.bpjs_v3.secret_key'));
        $providerCode = $providerCode ?: (string) config('services.bpjs_medical_record.provider_code');

        if ($consId === '' || $secretKey === '' || $providerCode === '') {
            throw new InvalidArgumentException('cons_id, secret_key, dan provider_code wajib tersedia untuk enkripsi dataMR.');
        }

        $encryptKey = $consId . $secretKey . $providerCode;
        $keyHash = hex2bin(hash('sha256', $encryptKey));
        $iv = substr(hex2bin(hash('sha256', $encryptKey)), 0, 16);

        $encrypted = openssl_encrypt(
            $compressed,
            'AES-256-CBC',
            $keyHash,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($encrypted === false) {
            throw new InvalidArgumentException('Gagal mengenkripsi payload medical record BPJS.');
        }

        return base64_encode($encrypted);
    }

    public function encode(array $bundle, ?string $consId = null, ?string $secretKey = null, ?string $providerCode = null): array
    {
        $compressed = $this->gzipJson($bundle);
        $encrypted = $this->encryptCompressedPayload($compressed, $consId, $secretKey, $providerCode);

        return [
            'gzip_payload' => base64_encode($compressed),
            'encrypted_payload' => $encrypted,
        ];
    }
}
