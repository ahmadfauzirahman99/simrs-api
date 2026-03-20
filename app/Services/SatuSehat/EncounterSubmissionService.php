<?php

namespace App\Services\SatuSehat;

use App\Services\SatuSehat\FHIR\Encounter;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\Http;

class EncounterSubmissionService
{
    public function __construct(
        protected OAuth2Client $oauth2Client,
        protected Encounter $encounterBuilder,
    ) {
    }

    public function send(array $payload): array
    {
        $normalized = $this->normalizeStatusHistory($payload);

        if (!$normalized['ok']) {
            return [
                'ok' => false,
                'code' => 422,
                'message' => $normalized['message'],
                'response' => null,
            ];
        }

        $patient = (object) [
            'phis_satu_sehat' => $payload['patient_satu_sehat'],
            'patient_name' => $payload['patient_name'],
        ];

        $encounter = $this->encounterBuilder->createEncounter(
            $payload['reg_id'],
            $payload['consultation_method'],
            $normalized['status_history'],
            $patient,
            $payload['doctor_satu_sehat'],
            $payload['unit_name'],
            $payload['location_id'],
            $payload['location_name']
        );

        if (is_string($encounter)) {
            return [
                'ok' => false,
                'code' => 422,
                'message' => $encounter,
                'response' => null,
            ];
        }

        $token = $this->oauth2Client->getValidToken();

        if (is_array($token)) {
            return [
                'ok' => false,
                'code' => 500,
                'message' => $token['msg'] ?? 'Gagal mengambil token SATUSEHAT',
                'response' => null,
            ];
        }

        $url = rtrim($this->oauth2Client->base_url, '/') . '/Encounter';

        $response = Http::withToken($token)
            ->acceptJson()
            ->contentType('application/json')
            ->timeout(60)
            ->post($url, $encounter);

        $data = $response->json();

        return [
            'ok' => $response->successful(),
            'code' => $response->status(),
            'message' => $response->successful()
                ? 'Berhasil kirim Encounter ke SATUSEHAT'
                : 'Gagal kirim Encounter ke SATUSEHAT',
            'response' => [
                'satusehat_response' => $data ?? (object) [],
                'request_payload' => $encounter,
                'status_history_fhir' => $normalized['status_history_fhir'],
            ],
        ];
    }

    protected function normalizeStatusHistory(array $payload): array
    {
        $tz = new DateTimeZone('Asia/Jakarta');

        $parseDt = function (?string $value) use ($tz): ?DateTimeImmutable {
            $value = trim((string) $value);

            if ($value === '') {
                return null;
            }

            try {
                return new DateTimeImmutable($value, $tz);
            } catch (\Throwable) {
                return null;
            }
        };

        $fmtSql = fn(?DateTimeImmutable $dt) => $dt ? $dt->format('Y-m-d H:i:s') : null;
        $fmtFhir = fn(?DateTimeImmutable $dt) => $dt ? $dt->format('c') : null;

        $dtArrived = $parseDt($payload['arrived'] ?? null);
        $dtInprogress = $parseDt($payload['inprogress'] ?? null);
        $dtFinished = $parseDt($payload['finished'] ?? null);

        if (!$dtArrived) {
            return [
                'ok' => false,
                'message' => 'Tanggal arrived tidak valid',
            ];
        }

        if ($dtInprogress && $dtInprogress < $dtArrived) {
            $dtInprogress = $dtArrived;
        }

        if ($dtInprogress && $dtFinished && $dtFinished < $dtInprogress) {
            $dtFinished = $dtInprogress;
        }

        if ($dtFinished && !$dtInprogress && $dtFinished < $dtArrived) {
            $dtFinished = $dtArrived;
        }

        $statusHistoryFhir = [];

        if ($dtArrived) {
            $end = $dtInprogress ?? $dtFinished ?? null;
            if ($end && $end < $dtArrived) {
                $end = $dtArrived;
            }

            $statusHistoryFhir[] = [
                'status' => 'arrived',
                'period' => array_filter([
                    'start' => $fmtFhir($dtArrived),
                    'end' => $fmtFhir($end),
                ], fn($value) => $value !== null),
            ];
        }

        if ($dtInprogress) {
            $end = $dtFinished ?? null;
            if ($end && $end < $dtInprogress) {
                $end = $dtInprogress;
            }

            $statusHistoryFhir[] = [
                'status' => 'in-progress',
                'period' => array_filter([
                    'start' => $fmtFhir($dtInprogress),
                    'end' => $fmtFhir($end),
                ], fn($value) => $value !== null),
            ];
        }

        if ($dtFinished) {
            $statusHistoryFhir[] = [
                'status' => 'finished',
                'period' => array_filter([
                    'start' => $fmtFhir($dtFinished),
                ], fn($value) => $value !== null),
            ];
        }

        return [
            'ok' => true,
            'status_history' => [
                'arrived' => $fmtSql($dtArrived),
                'inprogress' => $fmtSql($dtInprogress),
                'finished' => $fmtSql($dtFinished),
            ],
            'status_history_fhir' => $statusHistoryFhir,
        ];
    }
}
