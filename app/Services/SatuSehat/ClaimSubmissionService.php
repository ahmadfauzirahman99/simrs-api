<?php

namespace App\Services\SatuSehat;

use App\SatuSehat\Http\HttpException;
use App\SatuSehat\SatuSehatManager;

class ClaimSubmissionService
{
    public function __construct(private readonly SatuSehatManager $ss) {}

    public function send(array $payload): array
    {
        try {
            $builder = $this->ss->claim()
                ->setIdentifiers($payload['no_sep'], $payload['no_batch'])
                ->setPatient($payload['patient_satu_sehat'])
                ->setBillablePeriod($payload['start_date'], $payload['end_date'])
                ->setCreated($payload['created_at'] ?? now()->toIso8601String())
                ->setCoverage($payload['coverage_id'])
                ->setInvoice($payload['invoice_id'])
                ->setEncounterRef($payload['encounter_id'])
                ->setTotal((int) $payload['total']);

            if (!empty($payload['practitioner_id'])) {
                $builder->setEnterer($payload['practitioner_id'], $payload['practitioner_nik'] ?? '');
            }

            if (!empty($payload['location_id'])) {
                $builder->setLocation($payload['location_id']);
            }

            if (!empty($payload['inacbg_code'])) {
                $builder->setInacbg(
                    $payload['inacbg_code'],
                    $payload['inacbg_display'],
                    (int) ($payload['inacbg_value'] ?? $payload['total'])
                );
            }

            if (!empty($payload['tariff_class'])) {
                $builder->setTariffClass($payload['tariff_class'], $payload['tariff_class_display'] ?? 'Tarif Kelas A Pemerintah');
            }

            if (!empty($payload['claim_sub_type'])) {
                $builder->setSubType($payload['claim_sub_type']);
            }

            if (!empty($payload['upgrade_class'])) {
                $builder->setUpgradeClass($payload['upgrade_class'], $payload['upgrade_class_display'] ?? 'Tidak Ada Kenaikan Kelas');
            }

            foreach ($payload['diagnoses'] ?? [] as $index => $diag) {
                $builder->addDiagnosis($index + 1, $diag['icd10_code'], $diag['display'], $diag['type'] ?? 'discharge');
            }

            foreach ($payload['procedures'] ?? [] as $index => $proc) {
                $builder->addProcedure($index + 1, $proc['icd9cm_code'], $proc['display']);
            }

            $fhirPayload = $builder->toArray();
            $response    = $this->ss->fhir()->post('Claim', $fhirPayload);

            return [
                'ok'       => true,
                'code'     => 201,
                'message'  => 'Berhasil kirim Claim ke SATUSEHAT',
                'response' => [
                    'satusehat_response' => $response,
                    'request_payload'    => $fhirPayload,
                ],
            ];
        } catch (HttpException $e) {
            return [
                'ok'       => false,
                'code'     => $e->getStatusCode(),
                'message'  => 'Gagal kirim Claim ke SATUSEHAT',
                'response' => json_decode($e->getBody(), true),
            ];
        }
    }
}
