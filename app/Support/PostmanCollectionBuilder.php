<?php

namespace App\Support;

class PostmanCollectionBuilder
{
    public static function build(?string $userApiKey = null, string $baseUrl = 'http://127.0.0.1:8000'): array
    {
        $resolvedKey = $userApiKey ?: 'MASUKKAN_API_KEY_SANCTUM_ANDA_DISINI';

        return [
            'info' => [
                '_postman_id' => 'simrs-gateway-bpjs-v3',
                'name' => 'SIMRS Gateway BPJS & SATUSEHAT v3.0',
                'description' => "Koleksi Postman resmi untuk SIMRS Gateway BPJS Kesehatan (VClaim, Rujukan, SEP, Surat Kontrol, Antrean) dan Kemenkes SATUSEHAT FHIR.\n\nSistem mengurus enkripsi AES-256-CBC, kalkulasi signature HMAC-SHA256, dan dekripsi respons LZ-string secara otomatis.",
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'variable' => [
                [
                    'key' => 'base_url',
                    'value' => $baseUrl,
                    'type' => 'string',
                ],
                [
                    'key' => 'api_key',
                    'value' => $resolvedKey,
                    'type' => 'string',
                ],
                [
                    'key' => 'cons_id',
                    'value' => '12345',
                    'type' => 'string',
                ],
                [
                    'key' => 'secret_key',
                    'value' => 'userSecret123',
                    'type' => 'string',
                ],
                [
                    'key' => 'user_key',
                    'value' => 'userKeyBPJS99',
                    'type' => 'string',
                ],
            ],
            'auth' => [
                'type' => 'bearer',
                'bearer' => [
                    [
                        'key' => 'token',
                        'value' => '{{api_key}}',
                        'type' => 'string',
                    ],
                ],
            ],
            'item' => [
                // 1. Peserta BPJS
                [
                    'name' => '1. Peserta BPJS (VClaim)',
                    'item' => [
                        [
                            'name' => 'Cari Peserta by No. Kartu BPJS',
                            'request' => [
                                'method' => 'GET',
                                'header' => [
                                    ['key' => 'Accept', 'value' => 'application/json'],
                                ],
                                'url' => [
                                    'raw' => '{{base_url}}/api/v1/bpjs/peserta?nomor=0001234567890&tipe=1',
                                    'host' => ['{{base_url}}'],
                                    'path' => ['api', 'v1', 'bpjs', 'peserta'],
                                    'query' => [
                                        ['key' => 'nomor', 'value' => '0001234567890', 'description' => 'Nomor Kartu BPJS (13 Digit)'],
                                        ['key' => 'tipe', 'value' => '1', 'description' => '1: No. Kartu, 2: NIK KTP'],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Cari Peserta by NIK KTP',
                            'request' => [
                                'method' => 'GET',
                                'header' => [
                                    ['key' => 'Accept', 'value' => 'application/json'],
                                ],
                                'url' => [
                                    'raw' => '{{base_url}}/api/v1/bpjs/peserta?nomor=3201234567890001&tipe=2',
                                    'host' => ['{{base_url}}'],
                                    'path' => ['api', 'v1', 'bpjs', 'peserta'],
                                    'query' => [
                                        ['key' => 'nomor', 'value' => '3201234567890001', 'description' => 'Nomor NIK KTP (16 Digit)'],
                                        ['key' => 'tipe', 'value' => '2', 'description' => '1: No. Kartu, 2: NIK KTP'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],

                // 2. SEP
                [
                    'name' => '2. SEP (Surat Eligibilitas Peserta)',
                    'item' => [
                        [
                            'name' => 'Cari Detail SEP by Nomor SEP',
                            'request' => [
                                'method' => 'GET',
                                'header' => [['key' => 'Accept', 'value' => 'application/json']],
                                'url' => [
                                    'raw' => '{{base_url}}/api/v1/bpjs/sep?noSep=0001R0010124V000001',
                                    'host' => ['{{base_url}}'],
                                    'path' => ['api', 'v1', 'bpjs', 'sep'],
                                    'query' => [['key' => 'noSep', 'value' => '0001R0010124V000001']],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Histori Pelayanan Peserta',
                            'request' => [
                                'method' => 'GET',
                                'header' => [['key' => 'Accept', 'value' => 'application/json']],
                                'url' => [
                                    'raw' => '{{base_url}}/api/v1/bpjs/sep-riwayat?noKartu=0001234567890&tglMulai=2026-01-01&tglAkhir=2026-03-31',
                                    'host' => ['{{base_url}}'],
                                    'path' => ['api', 'v1', 'bpjs', 'sep-riwayat'],
                                    'query' => [
                                        ['key' => 'noKartu', 'value' => '0001234567890'],
                                        ['key' => 'tglMulai', 'value' => '2026-01-01'],
                                        ['key' => 'tglAkhir', 'value' => '2026-03-31'],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Insert / Buat SEP Baru',
                            'request' => [
                                'method' => 'POST',
                                'header' => [
                                    ['key' => 'Content-Type', 'value' => 'application/json'],
                                    ['key' => 'Accept', 'value' => 'application/json'],
                                ],
                                'body' => [
                                    'mode' => 'raw',
                                    'raw' => json_encode([
                                        'request' => [
                                            't_sep' => [
                                                'noKartu' => '0001234567890',
                                                'tglSep' => date('Y-m-d'),
                                                'ppkPelayanan' => '0001R001',
                                                'jnsPelayanan' => '2',
                                                'klsRawat' => ['klsRawatHak' => '3'],
                                                'noMR' => 'MR-89210',
                                                'rujukan' => [
                                                    'asalRujukan' => '1',
                                                    'tglRujukan' => date('Y-m-d'),
                                                    'noRujukan' => '0001R0010124B000001',
                                                    'ppkRujukan' => '00010001',
                                                ],
                                                'catatan' => 'Pemeriksaan Poli Penyakit Dalam',
                                                'diagAwal' => 'A09',
                                                'poli' => [
                                                    'tujuan' => 'INT',
                                                    'eksekutif' => '0',
                                                ],
                                                'cob' => ['cob' => '0'],
                                                'katarak' => ['katarak' => '0'],
                                                'jaminan' => [
                                                    'lakaLantas' => '0',
                                                    'penjamin' => ['tglKejadian' => ''],
                                                ],
                                                'tujuanKunj' => '0',
                                                'flagProcedure' => '',
                                                'kdPenunjang' => '',
                                                'assesmentPel' => '',
                                                'skdp' => [
                                                    'noSurat' => '',
                                                    'kodeDPJP' => '12345',
                                                ],
                                                'dpjpLayan' => '12345',
                                                'noTelp' => '081234567890',
                                                'user' => 'SIMRS-ADMIN',
                                            ],
                                        ],
                                    ], JSON_PRETTY_PRINT),
                                ],
                                'url' => [
                                    'raw' => '{{base_url}}/api/v1/bpjs/sep',
                                    'host' => ['{{base_url}}'],
                                    'path' => ['api', 'v1', 'bpjs', 'sep'],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Hapus SEP',
                            'request' => [
                                'method' => 'DELETE',
                                'header' => [
                                    ['key' => 'Content-Type', 'value' => 'application/json'],
                                    ['key' => 'Accept', 'value' => 'application/json'],
                                ],
                                'body' => [
                                    'mode' => 'raw',
                                    'raw' => json_encode([
                                        'request' => [
                                            't_sep' => [
                                                'noSep' => '0001R0010124V000001',
                                                'user' => 'SIMRS-ADMIN',
                                            ],
                                        ],
                                    ], JSON_PRETTY_PRINT),
                                ],
                                'url' => [
                                    'raw' => '{{base_url}}/api/v1/bpjs/sep',
                                    'host' => ['{{base_url}}'],
                                    'path' => ['api', 'v1', 'bpjs', 'sep'],
                                ],
                            ],
                        ],
                    ],
                ],

                // 3. Rujukan
                [
                    'name' => '3. Rujukan BPJS (Faskes 1 & Antar RS)',
                    'item' => [
                        [
                            'name' => 'Cari Rujukan by No. Rujukan (FKTP)',
                            'request' => [
                                'method' => 'GET',
                                'header' => [['key' => 'Accept', 'value' => 'application/json']],
                                'url' => [
                                    'raw' => '{{base_url}}/api/v1/bpjs/rujukan?noRujukan=0001R0010124B000001',
                                    'host' => ['{{base_url}}'],
                                    'path' => ['api', 'v1', 'bpjs', 'rujukan'],
                                    'query' => [['key' => 'noRujukan', 'value' => '0001R0010124B000001']],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Cari Rujukan by No. Kartu BPJS (FKTP)',
                            'request' => [
                                'method' => 'GET',
                                'header' => [['key' => 'Accept', 'value' => 'application/json']],
                                'url' => [
                                    'raw' => '{{base_url}}/api/v1/bpjs/rujukan/peserta?noKartu=0001234567890',
                                    'host' => ['{{base_url}}'],
                                    'path' => ['api', 'v1', 'bpjs', 'rujukan', 'peserta'],
                                    'query' => [['key' => 'noKartu', 'value' => '0001234567890']],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Cari Rujukan Antar RS (FKTL)',
                            'request' => [
                                'method' => 'GET',
                                'header' => [['key' => 'Accept', 'value' => 'application/json']],
                                'url' => [
                                    'raw' => '{{base_url}}/api/v1/bpjs/rujukan/rs?noRujukan=0001R0010124B000001',
                                    'host' => ['{{base_url}}'],
                                    'path' => ['api', 'v1', 'bpjs', 'rujukan', 'rs'],
                                    'query' => [['key' => 'noRujukan', 'value' => '0001R0010124B000001']],
                                ],
                            ],
                        ],
                    ],
                ],

                // 4. Surat Kontrol / SPRI
                [
                    'name' => '4. Surat Kontrol & SPRI',
                    'item' => [
                        [
                            'name' => 'Cari Surat Kontrol by Nomor',
                            'request' => [
                                'method' => 'GET',
                                'header' => [['key' => 'Accept', 'value' => 'application/json']],
                                'url' => [
                                    'raw' => '{{base_url}}/api/v1/bpjs/surat-kontrol?noSuratKontrol=0001R0010124K000001',
                                    'host' => ['{{base_url}}'],
                                    'path' => ['api', 'v1', 'bpjs', 'surat-kontrol'],
                                    'query' => [['key' => 'noSuratKontrol', 'value' => '0001R0010124K000001']],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Cari Surat Kontrol by Nomor SEP',
                            'request' => [
                                'method' => 'GET',
                                'header' => [['key' => 'Accept', 'value' => 'application/json']],
                                'url' => [
                                    'raw' => '{{base_url}}/api/v1/bpjs/surat-kontrol/sep?noSep=0001R0010124V000001',
                                    'host' => ['{{base_url}}'],
                                    'path' => ['api', 'v1', 'bpjs', 'surat-kontrol', 'sep'],
                                    'query' => [['key' => 'noSep', 'value' => '0001R0010124V000001']],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Buat Surat Kontrol Baru',
                            'request' => [
                                'method' => 'POST',
                                'header' => [
                                    ['key' => 'Content-Type', 'value' => 'application/json'],
                                    ['key' => 'Accept', 'value' => 'application/json'],
                                ],
                                'body' => [
                                    'mode' => 'raw',
                                    'raw' => json_encode([
                                        'request' => [
                                            'noSEP' => '0001R0010124V000001',
                                            'kodeDokter' => '12345',
                                            'poliKontrol' => 'INT',
                                            'tglRencanaKontrol' => date('Y-m-d', strtotime('+7 days')),
                                            'user' => 'SIMRS-ADMIN',
                                        ],
                                    ], JSON_PRETTY_PRINT),
                                ],
                                'url' => [
                                    'raw' => '{{base_url}}/api/v1/bpjs/surat-kontrol',
                                    'host' => ['{{base_url}}'],
                                    'path' => ['api', 'v1', 'bpjs', 'surat-kontrol'],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Buat SPRI (Surat Perintah Rawat Inap)',
                            'request' => [
                                'method' => 'POST',
                                'header' => [
                                    ['key' => 'Content-Type', 'value' => 'application/json'],
                                    ['key' => 'Accept', 'value' => 'application/json'],
                                ],
                                'body' => [
                                    'mode' => 'raw',
                                    'raw' => json_encode([
                                        'request' => [
                                            'noKartu' => '0001234567890',
                                            'kodeDokter' => '12345',
                                            'poliKontrol' => 'INT',
                                            'tglRencanaKontrol' => date('Y-m-d'),
                                            'user' => 'SIMRS-ADMIN',
                                        ],
                                    ], JSON_PRETTY_PRINT),
                                ],
                                'url' => [
                                    'raw' => '{{base_url}}/api/v1/bpjs/spri',
                                    'host' => ['{{base_url}}'],
                                    'path' => ['api', 'v1', 'bpjs', 'spri'],
                                ],
                            ],
                        ],
                    ],
                ],

                // 5. Antrean RS
                [
                    'name' => '5. Antrean RS (Antrol)',
                    'item' => [
                        [
                            'name' => 'Status Antrean Poli Hari Ini',
                            'request' => [
                                'method' => 'GET',
                                'header' => [['key' => 'Accept', 'value' => 'application/json']],
                                'url' => [
                                    'raw' => '{{base_url}}/api/v1/antrean/status?kodePoli=INT&tanggal=' . date('Y-m-d'),
                                    'host' => ['{{base_url}}'],
                                    'path' => ['api', 'v1', 'antrean', 'status'],
                                    'query' => [
                                        ['key' => 'kodePoli', 'value' => 'INT'],
                                        ['key' => 'tanggal', 'value' => date('Y-m-d')],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Ambil Nomor Antrean',
                            'request' => [
                                'method' => 'POST',
                                'header' => [
                                    ['key' => 'Content-Type', 'value' => 'application/json'],
                                    ['key' => 'Accept', 'value' => 'application/json'],
                                ],
                                'body' => [
                                    'mode' => 'raw',
                                    'raw' => json_encode([
                                        'nomorkartu' => '0001234567890',
                                        'nik' => '3201234567890001',
                                        'nohp' => '081234567890',
                                        'kodepoli' => 'INT',
                                        'namapoli' => 'Penyakit Dalam',
                                        'pasienbaru' => 0,
                                        'norm' => 'MR-89210',
                                        'tanggalperiksa' => date('Y-m-d'),
                                        'kodedokter' => 12345,
                                        'namadokter' => 'dr. Budi Santoso, Sp.PD',
                                        'jampraktek' => '08:00-12:00',
                                        'jeniskunjungan' => 1,
                                        'nomorreferensi' => '0001R0010124B000001',
                                    ], JSON_PRETTY_PRINT),
                                ],
                                'url' => [
                                    'raw' => '{{base_url}}/api/v1/antrean/ambil',
                                    'host' => ['{{base_url}}'],
                                    'path' => ['api', 'v1', 'antrean', 'ambil'],
                                ],
                            ],
                        ],
                    ],
                ],

                // 6. Referensi BPJS
                [
                    'name' => '6. Referensi BPJS',
                    'item' => [
                        [
                            'name' => 'Referensi Poli BPJS',
                            'request' => [
                                'method' => 'GET',
                                'header' => [['key' => 'Accept', 'value' => 'application/json']],
                                'url' => [
                                    'raw' => '{{base_url}}/api/v1/bpjs/referensi/poli?nama=INT',
                                    'host' => ['{{base_url}}'],
                                    'path' => ['api', 'v1', 'bpjs', 'referensi', 'poli'],
                                    'query' => [['key' => 'nama', 'value' => 'INT']],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Referensi Diagnosa ICD-10',
                            'request' => [
                                'method' => 'GET',
                                'header' => [['key' => 'Accept', 'value' => 'application/json']],
                                'url' => [
                                    'raw' => '{{base_url}}/api/v1/bpjs/referensi/diagnosa?kode=A09',
                                    'host' => ['{{base_url}}'],
                                    'path' => ['api', 'v1', 'bpjs', 'referensi', 'diagnosa'],
                                    'query' => [['key' => 'kode', 'value' => 'A09']],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Referensi Dokter DPJP',
                            'request' => [
                                'method' => 'GET',
                                'header' => [['key' => 'Accept', 'value' => 'application/json']],
                                'url' => [
                                    'raw' => '{{base_url}}/api/v1/bpjs/referensi/dokter-dpjp?pelayanan=2&tglPelayanan=' . date('Y-m-d') . '&kodeSpesialis=INT',
                                    'host' => ['{{base_url}}'],
                                    'path' => ['api', 'v1', 'bpjs', 'referensi', 'dokter-dpjp'],
                                    'query' => [
                                        ['key' => 'pelayanan', 'value' => '2'],
                                        ['key' => 'tglPelayanan', 'value' => date('Y-m-d')],
                                        ['key' => 'kodeSpesialis', 'value' => 'INT'],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Referensi Faskes BPJS',
                            'request' => [
                                'method' => 'GET',
                                'header' => [['key' => 'Accept', 'value' => 'application/json']],
                                'url' => [
                                    'raw' => '{{base_url}}/api/v1/bpjs/referensi/faskes?nama=Bhakti&tipe=2',
                                    'host' => ['{{base_url}}'],
                                    'path' => ['api', 'v1', 'bpjs', 'referensi', 'faskes'],
                                    'query' => [
                                        ['key' => 'nama', 'value' => 'Bhakti'],
                                        ['key' => 'tipe', 'value' => '2', 'description' => '1: Faskes 1 / Puskesmas, 2: RS / FKTL'],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Referensi Provinsi',
                            'request' => [
                                'method' => 'GET',
                                'header' => [['key' => 'Accept', 'value' => 'application/json']],
                                'url' => [
                                    'raw' => '{{base_url}}/api/v1/bpjs/referensi/provinsi',
                                    'host' => ['{{base_url}}'],
                                    'path' => ['api', 'v1', 'bpjs', 'referensi', 'provinsi'],
                                ],
                            ],
                        ],
                    ],
                ],

                // 7. Monitoring
                [
                    'name' => '7. Monitoring BPJS',
                    'item' => [
                        [
                            'name' => 'Monitoring Kunjungan Pasien',
                            'request' => [
                                'method' => 'GET',
                                'header' => [['key' => 'Accept', 'value' => 'application/json']],
                                'url' => [
                                    'raw' => '{{base_url}}/api/v1/bpjs/monitoring-kunjungan?tglPelayanan=' . date('Y-m-d') . '&jnsPelayanan=2',
                                    'host' => ['{{base_url}}'],
                                    'path' => ['api', 'v1', 'bpjs', 'monitoring-kunjungan'],
                                    'query' => [
                                        ['key' => 'tglPelayanan', 'value' => date('Y-m-d')],
                                        ['key' => 'jnsPelayanan', 'value' => '2', 'description' => '1: Rawat Inap, 2: Rawat Jalan'],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => 'Monitoring Status Klaim',
                            'request' => [
                                'method' => 'GET',
                                'header' => [['key' => 'Accept', 'value' => 'application/json']],
                                'url' => [
                                    'raw' => '{{base_url}}/api/v1/bpjs/monitoring-klaim?tglPulang=' . date('Y-m-d') . '&jnsPelayanan=1&status=1',
                                    'host' => ['{{base_url}}'],
                                    'path' => ['api', 'v1', 'bpjs', 'monitoring-klaim'],
                                    'query' => [
                                        ['key' => 'tglPulang', 'value' => date('Y-m-d')],
                                        ['key' => 'jnsPelayanan', 'value' => '1'],
                                        ['key' => 'status', 'value' => '1', 'description' => '1: Proses Verifikasi, 2: Pending, 3: Klaim'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],

                // 8. SatuSehat FHIR
                [
                    'name' => '8. SatuSehat Kemenkes (FHIR)',
                    'item' => [
                        [
                            'name' => 'SatuSehat: Auth Token OAuth2',
                            'request' => [
                                'method' => 'POST',
                                'header' => [
                                    ['key' => 'Content-Type', 'value' => 'application/json'],
                                    ['key' => 'Accept', 'value' => 'application/json'],
                                ],
                                'url' => [
                                    'raw' => '{{base_url}}/api/v1/satusehat/oauth/token',
                                    'host' => ['{{base_url}}'],
                                    'path' => ['api', 'v1', 'satusehat', 'oauth', 'token'],
                                ],
                            ],
                        ],
                        [
                            'name' => 'SatuSehat: Kirim Encounter (Kunjungan)',
                            'request' => [
                                'method' => 'POST',
                                'header' => [
                                    ['key' => 'Content-Type', 'value' => 'application/json'],
                                    ['key' => 'Accept', 'value' => 'application/json'],
                                ],
                                'body' => [
                                    'mode' => 'raw',
                                    'raw' => json_encode([
                                        'resourceType' => 'Encounter',
                                        'status' => 'arrived',
                                        'class' => [
                                            'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                                            'code' => 'AMB',
                                            'display' => 'ambulatory',
                                        ],
                                        'subject' => [
                                            'reference' => 'Patient/100000040001',
                                            'display' => 'Pasien Uji Coba',
                                        ],
                                    ], JSON_PRETTY_PRINT),
                                ],
                                'url' => [
                                    'raw' => '{{base_url}}/api/v1/satusehat/Encounter',
                                    'host' => ['{{base_url}}'],
                                    'path' => ['api', 'v1', 'satusehat', 'Encounter'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public static function writeStaticFile(): void
    {
        $dir = public_path('downloads');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $collection = self::build();
        file_put_contents(
            $dir . '/simrs-gateway-bpjs-postman-collection.json',
            json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}
