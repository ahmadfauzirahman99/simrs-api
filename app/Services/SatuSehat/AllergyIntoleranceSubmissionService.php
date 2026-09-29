<?php

namespace App\Services\SatuSehat;

use App\SatuSehat\Http\HttpException;
use App\SatuSehat\SatuSehatManager;
use App\SatuSehat\Support\DateHelper;

class AllergyIntoleranceSubmissionService
{
    public function __construct(private readonly SatuSehatManager $ss) {}

    public function send(array $payload): array
    {
        try {
            $builder = $this->ss->allergyIntolerance()
                ->setCode($payload['snomed_code'], $payload['snomed_display'])
                ->setPatient($payload['patient_satu_sehat'])
                ->setEncounter($payload['encounter_id'])
                ->setCategory($payload['category'] ?? 'medication')
                ->setType($payload['type'] ?? 'allergy')
                ->setCriticality($payload['criticality'] ?? 'low');

            if (!empty($payload['onset_datetime'])) {
                $builder->setOnsetDateTime(DateHelper::safeDateTime($payload['onset_datetime']));
            }

            if (!empty($payload['recorded_date'])) {
                $builder->setRecordedDate($payload['recorded_date']);
            }

            foreach ($payload['reactions'] ?? [] as $reaction) {
                $builder->addReaction(
                    $reaction['substance_code'],
                    $reaction['substance_display'],
                    $reaction['manifestation_code'],
                    $reaction['manifestation_display'],
                    $reaction['severity'] ?? 'mild'
                );
            }

            $fhirPayload = $builder->toArray();
            $response    = $this->ss->fhir()->post('AllergyIntolerance', $fhirPayload);

            return [
                'ok'       => true,
                'code'     => 201,
                'message'  => 'Berhasil kirim AllergyIntolerance ke SATUSEHAT',
                'response' => ['satusehat_response' => $response, 'request_payload' => $fhirPayload],
            ];
        } catch (HttpException $e) {
            return [
                'ok'       => false,
                'code'     => $e->getStatusCode(),
                'message'  => 'Gagal kirim AllergyIntolerance ke SATUSEHAT',
                'response' => json_decode($e->getBody(), true),
            ];
        }
    }
}
