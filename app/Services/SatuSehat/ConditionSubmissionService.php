<?php

namespace App\Services\SatuSehat;

use App\SatuSehat\Http\HttpException;
use App\SatuSehat\SatuSehatManager;
use App\Support\SatuSehat\DateHelper;

class ConditionSubmissionService
{
    public function __construct(private readonly SatuSehatManager $ss) {}

    public function send(array $payload): array
    {
        try {
            $onset = isset($payload['onset_datetime'])
                ? \App\SatuSehat\Support\DateHelper::safeDateTime($payload['onset_datetime'])
                : null;

            $builder = $this->ss->condition()
                ->addCategory($payload['category'] ?? 'diagnosis')
                ->addCode($payload['icd10_code'], $payload['icd10_display'])
                ->setSubject($payload['patient_satu_sehat'], $payload['patient_name'])
                ->setEncounter($payload['encounter_id']);

            if ($onset !== null) {
                $builder->setOnsetDateTime($onset);
            }

            $fhirPayload = $builder->toArray();
            $response    = $this->ss->fhir()->post('Condition', $fhirPayload);

            return [
                'ok'       => true,
                'code'     => 201,
                'message'  => 'Berhasil kirim Condition ke SATUSEHAT',
                'response' => [
                    'satusehat_response' => $response,
                    'request_payload'    => $fhirPayload,
                ],
            ];
        } catch (HttpException $e) {
            return [
                'ok'       => false,
                'code'     => $e->getStatusCode(),
                'message'  => 'Gagal kirim Condition ke SATUSEHAT',
                'response' => json_decode($e->getBody(), true),
            ];
        }
    }
}
