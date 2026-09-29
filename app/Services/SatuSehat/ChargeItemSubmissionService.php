<?php

namespace App\Services\SatuSehat;

use App\SatuSehat\Http\HttpException;
use App\SatuSehat\SatuSehatManager;

class ChargeItemSubmissionService
{
    public function __construct(private readonly SatuSehatManager $ss) {}

    public function send(array $payload): array
    {
        try {
            $builder = $this->ss->chargeItem()
                ->setCode($payload['kptl_code'], $payload['kptl_display'])
                ->setSubject($payload['patient_satu_sehat'])
                ->setEncounter($payload['encounter_id'])
                ->setAccount($payload['account_id'])
                ->setQuantity((int) ($payload['quantity'] ?? 1))
                ->setPrice((int) $payload['unit_price'], isset($payload['total_price']) ? (int) $payload['total_price'] : null);

            if (!empty($payload['start_date']) && !empty($payload['end_date'])) {
                $builder->setOccurrencePeriod($payload['start_date'], $payload['end_date']);
            }

            if (!empty($payload['practitioner_id'])) {
                $builder->setPerformer(
                    $payload['practitioner_id'],
                    $payload['snomed_code'] ?? '39677007',
                    $payload['snomed_display'] ?? 'Internal medicine specialist'
                );
            }

            $fhirPayload = $builder->toArray();
            $response    = $this->ss->fhir()->post('ChargeItem', $fhirPayload);

            return [
                'ok'       => true,
                'code'     => 201,
                'message'  => 'Berhasil kirim ChargeItem ke SATUSEHAT',
                'response' => [
                    'satusehat_response' => $response,
                    'request_payload'    => $fhirPayload,
                ],
            ];
        } catch (HttpException $e) {
            return [
                'ok'       => false,
                'code'     => $e->getStatusCode(),
                'message'  => 'Gagal kirim ChargeItem ke SATUSEHAT',
                'response' => json_decode($e->getBody(), true),
            ];
        }
    }
}
