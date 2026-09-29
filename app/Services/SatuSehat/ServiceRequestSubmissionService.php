<?php

namespace App\Services\SatuSehat;

use App\SatuSehat\Http\HttpException;
use App\SatuSehat\SatuSehatManager;
use App\SatuSehat\Support\DateHelper;

class ServiceRequestSubmissionService
{
    public function __construct(private readonly SatuSehatManager $ss) {}

    public function send(array $payload): array
    {
        try {
            $builder = $this->ss->serviceRequest()
                ->setStatus('active')
                ->setIntent('original-order')
                ->addCode($payload['snomed_code'], $payload['snomed_display'])
                ->setSubject($payload['patient_satu_sehat'], $payload['patient_name'])
                ->setEncounter($payload['encounter_id'])
                ->setRequester($payload['practitioner_id'], $payload['practitioner_name']);

            if (!empty($payload['priority'])) {
                $builder->setPriority($payload['priority']);
            }

            if (!empty($payload['occurrence_datetime'])) {
                $dt = DateHelper::safeDateTime($payload['occurrence_datetime']);
                if ($dt !== null) {
                    $builder->setOccurrenceDateTime($dt);
                }
            }

            if (!empty($payload['condition_id'])) {
                $builder->addReasonReference($payload['condition_id']);
            }

            $fhirPayload = $builder->toArray();
            $response    = $this->ss->fhir()->post('ServiceRequest', $fhirPayload);

            return [
                'ok'       => true,
                'code'     => 201,
                'message'  => 'Berhasil kirim ServiceRequest ke SATUSEHAT',
                'response' => [
                    'satusehat_response' => $response,
                    'request_payload'    => $fhirPayload,
                ],
            ];
        } catch (HttpException $e) {
            return [
                'ok'       => false,
                'code'     => $e->getStatusCode(),
                'message'  => 'Gagal kirim ServiceRequest ke SATUSEHAT',
                'response' => json_decode($e->getBody(), true),
            ];
        }
    }
}
