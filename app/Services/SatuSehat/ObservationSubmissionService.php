<?php

namespace App\Services\SatuSehat;

use App\SatuSehat\Http\HttpException;
use App\SatuSehat\SatuSehatManager;
use App\SatuSehat\Support\DateHelper;

class ObservationSubmissionService
{
    public function __construct(private readonly SatuSehatManager $ss) {}

    public function send(array $payload): array
    {
        try {
            $effective = DateHelper::safeDateTime($payload['effective_datetime']) ?? $payload['effective_datetime'];

            $builder = $this->ss->observation()
                ->addCategory($payload['category'] ?? 'vital-signs')
                ->addCode($payload['loinc_code'])
                ->setSubject($payload['patient_satu_sehat'], $payload['patient_name'])
                ->setEncounter($payload['encounter_id'])
                ->setEffectiveDateTime($effective)
                ->setValueQuantity((float) $payload['value'], $payload['unit']);

            $fhirPayload = $builder->toArray();
            $response    = $this->ss->fhir()->post('Observation', $fhirPayload);

            return [
                'ok'       => true,
                'code'     => 201,
                'message'  => 'Berhasil kirim Observation ke SATUSEHAT',
                'response' => [
                    'satusehat_response' => $response,
                    'request_payload'    => $fhirPayload,
                ],
            ];
        } catch (HttpException $e) {
            return [
                'ok'       => false,
                'code'     => $e->getStatusCode(),
                'message'  => 'Gagal kirim Observation ke SATUSEHAT',
                'response' => json_decode($e->getBody(), true),
            ];
        }
    }
}
