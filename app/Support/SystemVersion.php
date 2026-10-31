<?php

namespace App\Support;

class SystemVersion
{
    public const CURRENT_VERSION = 'v3.2.0';
    public const RELEASE_DATE = '31 Oktober 2026';
    public const RELEASE_NAME = 'Auto-Mock Simulator & Multi-Poli APM';

    /**
     * Riwayat rilis & changelog sistem SIMRS Gateway
     */
    public static function releases(): array
    {
        return [
            [
                'version' => 'v3.2.0',
                'date' => '31 Oktober 2026',
                'title' => 'Auto-Mock Postman & Fix Timezone WIB',
                'badge' => 'Versi Aktif',
                'badge_color' => 'emerald',
                'highlights' => [
                    'Fitur Auto-Mock Postman: request Postman ke 209 endpoint BPJS otomatis merespons data dummy sukses (HTTP 200) tanpa melempar cURL error 500 walau kredensial belum diisi.',
                    'Dukungan header `X-Mock-Mode: true` di Postman Collection untuk beralih antara live bridging dan simulasi instan.',
                    'Pembaruan zona waktu global ke Asia/Jakarta (WIB) pada database, Docker container, dan dashboard admin.',
                    'Migrasi penyesuaian otomatis untuk 278+ rekaman log lawas dari UTC ke WIB.',
                ],
            ],
            [
                'version' => 'v3.1.0',
                'date' => '30 Oktober 2026',
                'title' => 'Transisi Open Source MIT & Dukungan Saweria',
                'badge' => 'Open Source',
                'badge_color' => 'blue',
                'highlights' => [
                    'Transisi lisensi resmi ke MIT License (100% Free & Open Source).',
                    'Integrasi portal donasi komunitas pengembang melalui Saweria.',
                    'Penyediaan link download langsung Source Code (.ZIP) dan Postman Collection 209 Endpoint gratis tanpa registrasi.',
                    'Pembaruan dokumentasi README dengan visual badge, diagram alur data PMK 24, dan panduan APM.',
                ],
            ],
            [
                'version' => 'v3.0.0',
                'date' => '25 Oktober 2026',
                'title' => 'Modul APM Kiosk, Display TV & Antrean BPJS',
                'badge' => 'Mayor Release',
                'badge_color' => 'purple',
                'highlights' => [
                    'Layar Sentuh Anjungan Pendaftaran Mandiri (APM Kiosk) lobby RS untuk check-in Mobile JKN & registrasi mandiri.',
                    'Display TV Antrean Poli dengan pemanggilan suara otomatis (Text-to-Speech) bebas lag.',
                    'Layar Panggil Pasien Dokter & Perawat per poliklinik.',
                    'Generator SEP & pencetakan lembar eligibilitas peserta VClaim.',
                    'Sinkronisasi otomatis Task ID 1 sampai 7 BPJS Antrol via background command.',
                ],
            ],
            [
                'version' => 'v2.5.0',
                'date' => '15 Oktober 2026',
                'title' => 'SATUSEHAT FHIR R4 Bundle Submissions',
                'badge' => 'Regulasi Kemenkes',
                'badge_color' => 'amber',
                'highlights' => [
                    'Manajemen token OAuth2 SATUSEHAT otomatis dengan auto-refresh sebelum kedaluwarsa.',
                    'Pengiriman resource FHIR Encounter, Condition, Observation, Procedure, dan ServiceRequest.',
                    'Dukungan billing & klaim integrasi: ChargeItem, Invoice, dan Coverage.',
                ],
            ],
            [
                'version' => 'v2.0.0',
                'date' => '01 Oktober 2026',
                'title' => 'Studio Testing Web & 209 Endpoint Simulator',
                'badge' => 'Studio UI',
                'badge_color' => 'slate',
                'highlights' => [
                    'Peluncuran Web Studio Tester interaktif untuk pengujian 209 endpoint BPJS tanpa pasien riil.',
                    'Mesin enkripsi HMAC-SHA256 timestamp signature & dekripsi LZ-string otomatis.',
                    'Audit trail dan telemetri request latensi di dashboard administrator.',
                ],
            ],
        ];
    }
}
