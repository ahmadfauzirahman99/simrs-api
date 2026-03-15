<?php

namespace App\Http\Controllers\Api\SatuSehat;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\SatuSehat\FHIR\Encounter;
use App\Services\SatuSehat\OAuth2Client;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\Http;

class EncounterController extends Controller
{

    /**
     * Kirim Encounter ke SATUSEHAT
     *
     * Semua data dikirim dari body request.
     * 
     */
    public function send(Request $request)
    {
        $request->validate([
            'reg_id' => ['required', 'string'],
            'consultation_method' => ['required', 'string'], // RAJAL / IGD / RANAP / HOMECARE / TELEKONSULTASI

            'arrived' => ['required', 'date'],
            'inprogress' => ['nullable', 'date'],
            'finished' => ['nullable', 'date'],

            'patient_satu_sehat' => ['required', 'string'],
            'patient_name' => ['required', 'string'],

            'doctor_satu_sehat' => ['required', 'string'],
            'unit_name' => ['required', 'string'],

            'location_id' => ['required', 'string'],
            'location_name' => ['required', 'string'],
        ]);

        $tz = new DateTimeZone('Asia/Jakarta');

        $parseDt = function (?string $value) use ($tz): ?DateTimeImmutable {
            $value = trim((string) $value);
            if ($value === '') {
                return null;
            }

            try {
                return new DateTimeImmutable($value, $tz);
            } catch (\Throwable $e) {
                return null;
            }
        };

        $fmtSql = fn(?DateTimeImmutable $dt) => $dt ? $dt->format('Y-m-d H:i:s') : null;
        $fmtFhir = fn(?DateTimeImmutable $dt) => $dt ? $dt->format('c') : null;

        /**
         * ==========================================================
         * 1. Parse dan normalisasi timestamp
         * ==========================================================
         */
        $dtArrived = $parseDt($request->arrived);
        $dtInprogress = $parseDt($request->inprogress);
        $dtFinished = $parseDt($request->finished);

        if (!$dtArrived) {
            return ApiResponse::error('Tanggal arrived tidak valid', 422);
        }

        // Pastikan urutan waktu benar: arrived <= inprogress <= finished
        if ($dtArrived && $dtInprogress && $dtInprogress < $dtArrived) {
            $dtInprogress = $dtArrived;
        }

        if ($dtInprogress && $dtFinished && $dtFinished < $dtInprogress) {
            $dtFinished = $dtInprogress;
        }

        if ($dtArrived && $dtFinished && !$dtInprogress && $dtFinished < $dtArrived) {
            $dtFinished = $dtArrived;
        }

        /**
         * ==========================================================
         * 2. Status history versi internal / EncounterService
         * ==========================================================
         * EncounterService yang kamu punya menggunakan format ini:
         * [
         *   'arrived' => 'Y-m-d H:i:s',
         *   'inprogress' => 'Y-m-d H:i:s',
         *   'finished' => 'Y-m-d H:i:s'
         * ]
         */
        $statusHistory = [
            'arrived' => $fmtSql($dtArrived),
            'inprogress' => $fmtSql($dtInprogress),
            'finished' => $fmtSql($dtFinished),
        ];

        /**
         * ==========================================================
         * 3. Optional debug FHIR statusHistory
         * ==========================================================
         * Ini tidak wajib dikirim ke EncounterService,
         * tapi saya tampilkan di response agar mudah debugging.
         */
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
                ], fn($v) => $v !== null),
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
                ], fn($v) => $v !== null),
            ];
        }

        if ($dtFinished) {
            $statusHistoryFhir[] = [
                'status' => 'finished',
                'period' => array_filter([
                    'start' => $fmtFhir($dtFinished),
                ], fn($v) => $v !== null),
            ];
        }

        /**
         * ==========================================================
         * 4. Bentuk data pasien
         * ==========================================================
         */
        $pasien = (object) [
            'phis_satu_sehat' => $request->patient_satu_sehat,
            'patient_name' => $request->patient_name,
        ];

        /**
         * ==========================================================
         * 5. Buat payload Encounter
         * ==========================================================
         */
        $encounterService = new Encounter();

        $encounter = $encounterService->createEncounter(
            $request->reg_id,
            $request->consultation_method,
            $statusHistory,
            $pasien,
            $request->doctor_satu_sehat,
            $request->unit_name,
            $request->location_id,
            $request->location_name
        );

        if (is_string($encounter)) {
            return ApiResponse::error($encounter, 422);
        }

        /**
         * ==========================================================
         * 6. Ambil token SATUSEHAT
         * ==========================================================
         */
        $oauth = new OAuth2Client();
        $token = $oauth->getValidToken();

        if (is_array($token)) {
            return ApiResponse::error($token['msg'] ?? 'Gagal mengambil token SATUSEHAT', 500);
        }

        /**
         * ==========================================================
         * 7. POST ke endpoint Encounter SATUSEHAT
         * ==========================================================
         */
        $url = rtrim($oauth->base_url, '/') . '/Encounter';

        $response = Http::withToken($token)
            ->acceptJson()
            ->contentType('application/json')
            ->timeout(60)
            ->post($url, $encounter);

        $data = $response->json();

        /**
         * ==========================================================
         * 8. Response ke client
         * ==========================================================
         */
        if ($response->successful()) {
            return response()->json([
                'metaData' => [
                    'code' => '200',
                    'message' => 'Berhasil kirim Encounter ke SATUSEHAT',
                ],
                'response' => [
                    'satusehat_response' => $data,
                    'request_payload' => $encounter,
                    'status_history_fhir' => $statusHistoryFhir,
                ],
            ], 200);
        }

        return response()->json([
            'metaData' => [
                'code' => (string) $response->status(),
                'message' => 'Gagal kirim Encounter ke SATUSEHAT',
            ],
            'response' => [
                'satusehat_response' => $data ?? (object) [],
                'request_payload' => $encounter,
                'status_history_fhir' => $statusHistoryFhir,
            ],
        ], $response->status());
    }
}
