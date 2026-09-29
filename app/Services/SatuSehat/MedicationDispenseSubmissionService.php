<?php

namespace App\Services\SatuSehat;

use App\SatuSehat\Http\HttpException;
use App\SatuSehat\SatuSehatManager;

class MedicationDispenseSubmissionService
{
    public function __construct(private readonly SatuSehatManager $ss) {}

    public function send(array $payload): array
    {
        try {
            $builder = $this->ss->medicationDispense()
                ->setMedication($payload['kfa_code'], $payload['kfa_display'])
                ->setPatient($payload['patient_satu_sehat'])
                ->setEncounter($payload['encounter_id'])
                ->setStatus($payload['status'] ?? 'completed')
                ->setCategory($payload['category'] ?? 'outpatient')
                ->setWhenHandedOver($payload['when_handed_over'] ?? now()->toIso8601String());

            if (!empty($payload['dispensing_no'])) {
                $builder->setDispensingNo($payload['dispensing_no']);
            }

            if (!empty($payload['practitioner_id'])) {
                $builder->setPerformer($payload['practitioner_id']);
            }

            if (!empty($payload['medication_request_id'])) {
                $builder->setMedicationRequest($payload['medication_request_id']);
            }

            if (!empty($payload['quantity'])) {
                $builder->setQuantity((float) $payload['quantity'], $payload['quantity_unit'] ?? 'TAB');
            }

            if (!empty($payload['days_supply'])) {
                $builder->setDaysSupply((int) $payload['days_supply']);
            }

            foreach ($payload['dosage_instructions'] ?? [] as $di) {
                $builder->addDosageInstruction(
                    (int) ($di['frequency'] ?? 3),
                    (int) ($di['period'] ?? 1),
                    $di['period_unit'] ?? 'd',
                    (float) ($di['dose_value'] ?? 1),
                    $di['dose_unit'] ?? 'TAB'
                );
            }

            $fhirPayload = $builder->toArray();
            $response    = $this->ss->fhir()->post('MedicationDispense', $fhirPayload);

            return [
                'ok'       => true,
                'code'     => 201,
                'message'  => 'Berhasil kirim MedicationDispense ke SATUSEHAT',
                'response' => ['satusehat_response' => $response, 'request_payload' => $fhirPayload],
            ];
        } catch (HttpException $e) {
            return [
                'ok'       => false,
                'code'     => $e->getStatusCode(),
                'message'  => 'Gagal kirim MedicationDispense ke SATUSEHAT',
                'response' => json_decode($e->getBody(), true),
            ];
        }
    }
}
