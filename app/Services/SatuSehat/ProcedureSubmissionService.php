<?php

namespace App\Services\SatuSehat;

use App\SatuSehat\Http\HttpException;
use App\SatuSehat\SatuSehatManager;
use App\SatuSehat\Support\DateHelper;

class ProcedureSubmissionService
{
    public function __construct(private readonly SatuSehatManager $ss) {}

    public function send(array $payload): array
    {
        try {
            $start = DateHelper::safeDateTime($payload['performed_start']) ?? $payload['performed_start'];
            $end   = isset($payload['performed_end'])
                ? DateHelper::safeDateTime($payload['performed_end'])
                : null;

            $builder = $this->ss->procedure()
                ->setStatus('completed')
                ->addCode($payload['snomed_code'], $payload['snomed_display'])
                ->setSubject($payload['patient_satu_sehat'], $payload['patient_name'])
                ->setEncounter($payload['encounter_id'])
                ->setPerformedPeriod($start, $end)
                ->addPerformer($payload['practitioner_id'], $payload['practitioner_name']);

            $fhirPayload = $builder->toArray();
            $response    = $this->ss->fhir()->post('Procedure', $fhirPayload);

            return [
                'ok'       => true,
                'code'     => 201,
                'message'  => 'Berhasil kirim Procedure ke SATUSEHAT',
                'response' => [
                    'satusehat_response' => $response,
                    'request_payload'    => $fhirPayload,
                ],
            ];
        } catch (HttpException $e) {
            return [
                'ok'       => false,
                'code'     => $e->getStatusCode(),
                'message'  => 'Gagal kirim Procedure ke SATUSEHAT',
                'response' => json_decode($e->getBody(), true),
            ];
        }
    }
}
