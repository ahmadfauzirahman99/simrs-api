<?php

namespace App\Services\SatuSehat;

use App\SatuSehat\Http\HttpException;
use App\SatuSehat\SatuSehatManager;

class MedicationRequestSubmissionService
{
    public function __construct(private readonly SatuSehatManager $ss) {}

    public function send(array $payload): array
    {
        try {
            $builder = $this->ss->medicationRequest()
                ->setMedication($payload['kfa_code'], $payload['kfa_display'], $payload['prescription_no'] ?? '')
                ->setPatient($payload['patient_satu_sehat'])
                ->setEncounter($payload['encounter_id'])
                ->setAuthoredOn($payload['authored_on'] ?? now()->toIso8601String())
                ->setCategory($payload['category'] ?? 'outpatient');

            if (!empty($payload['practitioner_id'])) {
                $builder->setRequester($payload['practitioner_id']);
            }

            foreach ($payload['dosage_instructions'] ?? [] as $di) {
                $builder->addDosageInstruction(
                    (int) ($di['frequency'] ?? 3),
                    (int) ($di['period'] ?? 1),
                    $di['period_unit'] ?? 'd',
                    (float) ($di['dose_value'] ?? 1),
                    $di['dose_unit'] ?? 'TAB',
                    $di['route_code'] ?? 'oral',
                    $di['route_display'] ?? 'Oral'
                );
            }

            if (!empty($payload['dispense_quantity'])) {
                $builder->setDispenseRequest(
                    (int) $payload['dispense_quantity'],
                    $payload['dispense_unit'] ?? 'TAB',
                    (int) ($payload['days_supply'] ?? 3)
                );
            }

            $fhirPayload = $builder->toArray();
            $response    = $this->ss->fhir()->post('MedicationRequest', $fhirPayload);

            return [
                'ok'       => true,
                'code'     => 201,
                'message'  => 'Berhasil kirim MedicationRequest ke SATUSEHAT',
                'response' => ['satusehat_response' => $response, 'request_payload' => $fhirPayload],
            ];
        } catch (HttpException $e) {
            return [
                'ok'       => false,
                'code'     => $e->getStatusCode(),
                'message'  => 'Gagal kirim MedicationRequest ke SATUSEHAT',
                'response' => json_decode($e->getBody(), true),
            ];
        }
    }
}
