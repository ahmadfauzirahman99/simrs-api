<?php

namespace App\Services\SatuSehat;

use App\SatuSehat\Http\HttpException;
use App\SatuSehat\SatuSehatManager;

class AccountSubmissionService
{
    public function __construct(private readonly SatuSehatManager $ss) {}

    public function send(array $payload): array
    {
        try {
            $fhirPayload = $this->ss->account()
                ->setPatient($payload['patient_satu_sehat'], $payload['patient_name'])
                ->setServicePeriod($payload['start_date'], $payload['end_date'])
                ->setCoverage($payload['coverage_id'])
                ->toArray();

            $response = $this->ss->fhir()->post('Account', $fhirPayload);

            return [
                'ok'       => true,
                'code'     => 201,
                'message'  => 'Berhasil kirim Account ke SATUSEHAT',
                'response' => [
                    'satusehat_response' => $response,
                    'request_payload'    => $fhirPayload,
                ],
            ];
        } catch (HttpException $e) {
            return [
                'ok'       => false,
                'code'     => $e->getStatusCode(),
                'message'  => 'Gagal kirim Account ke SATUSEHAT',
                'response' => json_decode($e->getBody(), true),
            ];
        }
    }
}
