<?php

namespace App\Services\SatuSehat;

use App\SatuSehat\Http\HttpException;
use App\SatuSehat\SatuSehatManager;

class ImmunizationSubmissionService
{
    public function __construct(private readonly SatuSehatManager $ss) {}

    public function send(array $payload): array
    {
        try {
            $builder = $this->ss->immunization()
                ->setVaccine($payload['kfa_code'], $payload['kfa_display'])
                ->setPatient($payload['patient_satu_sehat'])
                ->setEncounter($payload['encounter_id'])
                ->setOccurrenceDateTime($payload['occurrence_datetime'] ?? now()->toIso8601String())
                ->setStatus($payload['status'] ?? 'completed');

            if (!empty($payload['immunization_no'])) {
                $builder->setImmunizationNo($payload['immunization_no']);
            }

            if (!empty($payload['location_id'])) {
                $builder->setLocation($payload['location_id']);
            }

            if (!empty($payload['practitioner_id'])) {
                $builder->addPerformer($payload['practitioner_id']);
            }

            if (!empty($payload['lot_number'])) {
                $builder->setLotNumber($payload['lot_number']);
            }

            if (!empty($payload['site_code'])) {
                $builder->setSite($payload['site_code'], $payload['site_display'] ?? '');
            }

            if (!empty($payload['route_code'])) {
                $builder->setRoute($payload['route_code'], $payload['route_display'] ?? '');
            }

            if (!empty($payload['dose_value'])) {
                $builder->setDoseQuantity((float) $payload['dose_value'], $payload['dose_unit'] ?? 'mL');
            }

            $fhirPayload = $builder->toArray();
            $response    = $this->ss->fhir()->post('Immunization', $fhirPayload);

            return [
                'ok'       => true,
                'code'     => 201,
                'message'  => 'Berhasil kirim Immunization ke SATUSEHAT',
                'response' => ['satusehat_response' => $response, 'request_payload' => $fhirPayload],
            ];
        } catch (HttpException $e) {
            return [
                'ok'       => false,
                'code'     => $e->getStatusCode(),
                'message'  => 'Gagal kirim Immunization ke SATUSEHAT',
                'response' => json_decode($e->getBody(), true),
            ];
        }
    }
}
