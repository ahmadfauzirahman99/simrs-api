<?php

namespace App\Services\SatuSehat;

use App\SatuSehat\Http\HttpException;
use App\SatuSehat\SatuSehatManager;

class DiagnosticReportSubmissionService
{
    public function __construct(private readonly SatuSehatManager $ss) {}

    public function send(array $payload): array
    {
        try {
            $builder = $this->ss->diagnosticReport()
                ->setCode($payload['loinc_code'], $payload['loinc_display'])
                ->setSubject($payload['patient_satu_sehat'])
                ->setEncounter($payload['encounter_id'])
                ->setCategory($payload['category'] ?? 'lab')
                ->setStatus($payload['status'] ?? 'final')
                ->setEffectiveDateTime($payload['effective_datetime'] ?? now()->toIso8601String())
                ->setIssued($payload['issued'] ?? now()->toIso8601String());

            if (!empty($payload['report_no'])) {
                $builder->setReportNo($payload['report_no']);
            }

            foreach ($payload['performers'] ?? [] as $pId) {
                $builder->addPerformer($pId);
            }

            foreach ($payload['results'] ?? [] as $obsId) {
                $builder->addResult($obsId);
            }

            $fhirPayload = $builder->toArray();
            $response    = $this->ss->fhir()->post('DiagnosticReport', $fhirPayload);

            return [
                'ok'       => true,
                'code'     => 201,
                'message'  => 'Berhasil kirim DiagnosticReport ke SATUSEHAT',
                'response' => ['satusehat_response' => $response, 'request_payload' => $fhirPayload],
            ];
        } catch (HttpException $e) {
            return [
                'ok'       => false,
                'code'     => $e->getStatusCode(),
                'message'  => 'Gagal kirim DiagnosticReport ke SATUSEHAT',
                'response' => json_decode($e->getBody(), true),
            ];
        }
    }
}
