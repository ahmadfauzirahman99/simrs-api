<?php

namespace App\Services\SatuSehat;

use App\SatuSehat\Http\HttpException;
use App\SatuSehat\SatuSehatManager;

class CoverageSubmissionService
{
    public function __construct(private readonly SatuSehatManager $ss) {}

    public function send(array $payload): array
    {
        try {
            $fhirPayload = $this->ss->coverage()
                ->setNomorKartu($payload['nomor_kartu'])
                ->setBeneficiary($payload['patient_satu_sehat'])
                ->setKelas($payload['kelas'] ?? '1')
                ->setPpu($payload['ppu_type'] ?? 'ppu-pegawai-swasta', $payload['ppu_name'] ?? 'Pegawai Swasta')
                ->toArray();

            $response = $this->ss->fhir()->post('Coverage', $fhirPayload);

            return [
                'ok'       => true,
                'code'     => 201,
                'message'  => 'Berhasil kirim Coverage ke SATUSEHAT',
                'response' => [
                    'satusehat_response' => $response,
                    'request_payload'    => $fhirPayload,
                ],
            ];
        } catch (HttpException $e) {
            return [
                'ok'       => false,
                'code'     => $e->getStatusCode(),
                'message'  => 'Gagal kirim Coverage ke SATUSEHAT',
                'response' => json_decode($e->getBody(), true),
            ];
        }
    }
}
