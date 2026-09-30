<?php

namespace App\Support\Bpjs;

class BpjsCatalogSourceLoader
{
    protected static array $fieldDefaults = [
        'nomor' => '0001234567890',
        'nomorkartu' => '0001234567890',
        'nokartu' => '0001234567890',
        'no_kartu' => '0001234567890',
        'noka' => '0001234567890',
        'nik' => '3319022010810007',
        'no_sep' => '0301R0110326V000001',
        'nosep' => '0301R0110326V000001',
        'noidentitas' => '3319022010810007',
        'kodepoli' => 'ANA',
        'kdpoli' => 'ANA',
        'namapoli' => 'Poli Anak',
        'kodedokter' => '12345',
        'kddpjp' => '12345',
        'namadokter' => 'dr. Budi Santoso, Sp.A',
        'kodeppk' => '0138U020',
        'ppk' => '0138U020',
        'start' => '1',
        'limit' => '10',
        'tanggal' => null,
        'tgl' => null,
        'tglsep' => null,
        'tglmasuk' => null,
        'tglpelayanan' => null,
        'tanggalperiksa' => null,
        'bulan' => null,
        'tahun' => null,
        'jnspelayanan' => '2',
        'tipe' => '1',
        'kodebooking' => null,
        'kodekelas' => 'VIP',
        'koderuang' => 'RG01',
        'namaruang' => 'Ruang Anggrek VIP',
        'kapasitas' => '10',
        'tersedia' => '4',
        'tersediapria' => '2',
        'tersediawanita' => '2',
        'tersediapriawanita' => '0',
        'norm' => 'MR091823',
        'nohp' => '081234567890',
        'keterangan' => 'Pasien rujukan FKTP',
        'parameter' => '0001234567890',
        'diagnosa' => 'A09',
        'kodeobat' => '11250805294',
        'namaobat' => 'Paracetamol 500mg',
        'jeniskunjungan' => '1',
        'nomorreferensi' => '0138U0200926P000001',
        'nomorantrean' => 'A-012',
        'angkaantrean' => '12',
        'estimasidilayani' => '1727600000000',
        'sisakuotajkn' => '15',
        'kuotajkn' => '30',
        'sisakuotanonjkn' => '10',
        'kuotanonjkn' => '20',
        'pasienbaru' => '0',
        'jampraktek' => '08:00-12:00',
    ];

    public static function load(?string $sourceDir = null): array
    {
        $dir = $sourceDir ?: dirname(__DIR__, 3) . '/resources/bpjs-catalog-source';
        if (!is_dir($dir)) {
            return [];
        }

        $files = glob(rtrim($dir, '/') . '/*.txt') ?: [];
        sort($files);

        $groups = [];
        foreach ($files as $file) {
            $moduleKey = basename($file, '.txt');
            $group = self::parseFile($file, $moduleKey);
            if (!empty($group['items'])) {
                $groups[] = $group;
            }
        }

        return $groups;
    }

    public static function parseFile(string $filePath, string $moduleKey): array
    {
        $content = file_get_contents($filePath) ?: '';
        $blocks = preg_split('/(?=\{BASE URL\}\/)/i', $content) ?: [];
        $items = [];

        foreach ($blocks as $idx => $block) {
            if (!preg_match('/\{BASE URL\}(\/[^\s\n\r]+)/i', $block, $epMatch)) {
                continue;
            }

            $endpoint = trim($epMatch[1]);
            preg_match('/Method\s*:\s*(GET|POST|PUT|DELETE|PATCH)/i', $block, $mMatch);
            $method = strtoupper($mMatch[1] ?? 'GET');

            // Find title in lines preceding {BASE URL}
            $beforeEp = substr($block, 0, strpos($block, '{BASE URL}'));
            $lines = array_filter(array_map('trim', explode("\n", $beforeEp)));
            $title = end($lines) ?: $endpoint;
            if (preg_match('/^(BASE URL|Method|Format|Header|Fungsi)\b/i', $title)) {
                $title = prev($lines) ?: $endpoint;
            }

            // Extract path parameters: {param}
            preg_match_all('/\{([^}]+)\}/', $endpoint, $paramMatches);
            $fields = [];
            foreach ($paramMatches[1] ?? [] as $param) {
                $cleanParam = strtolower(trim(preg_replace('/[^a-z0-9_]/i', '', $param)));
                $defaultValue = self::getDefaultValue($cleanParam);

                $fields[] = [
                    'key' => $param,
                    'label' => ucwords(str_replace(['_', '-'], ' ', $param)),
                    'type' => 'text',
                    'value' => $defaultValue,
                ];
            }

            // Extract JSON blocks
            $jsonBlocks = self::extractAllJsonBlocks($block);
            $requestJson = null;
            $responseJson = null;

            foreach ($jsonBlocks as $candidate) {
                $cleaned = self::cleanJsonPlaceholders($candidate);
                $decoded = json_decode($cleaned, true);
                if (!is_array($decoded)) {
                    continue;
                }

                if (isset($decoded['metaData']) || isset($decoded['response']) || isset($decoded['metadata'])) {
                    $responseJson = $decoded;
                } elseif (isset($decoded['request']) || in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
                    $requestJson = $decoded;
                }
            }

            // Fallback for missing or placeholder request body
            if ($requestJson !== null) {
                $requestJson = self::replaceJsonArrayPlaceholders($requestJson);
            } elseif (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
                $requestJson = self::generateDefaultRequestBody($moduleKey, $endpoint, $method);
            }

            // Fallback for missing/empty mock response
            $responseJson = self::ensureNonEmptyMockResponse($moduleKey, $endpoint, $method, $responseJson);

            $actionKey = strtolower($moduleKey . '-' . preg_replace('/[^a-z0-9]+/i', '-', trim($title, '/')) . "-{$idx}");
            $isPostOrPut = in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true);

            $items[] = [
                'id' => $actionKey,
                'title' => $title,
                'method' => $method,
                'path' => "/api/v1/bpjs/{$moduleKey}" . $endpoint,
                'description' => "Modul BPJS {$moduleKey} - {$title}",
                'mode' => $isPostOrPut ? 'json' : 'query',
                'fields' => $fields,
                'jsonTemplate' => $isPostOrPut
                    ? json_encode($requestJson ?? (object) [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                    : '{}',
                'mockResponses' => [
                    'success' => $responseJson,
                    'failed' => [
                        'metaData' => ['code' => '400', 'message' => 'Parameter tidak valid atau data tidak ditemukan pada sistem BPJS'],
                        'response' => (object) [],
                    ],
                ],
            ];
        }

        $labels = [
            'vclaim' => 'VClaim',
            'antreanrs' => 'Antrean RS',
            'antreanfktp' => 'Antrean FKTP',
            'apotek' => 'Apotek',
            'pcare' => 'PCare',
            'aplicares' => 'Aplicares',
            'icare' => 'i-Care',
            'rekammedis' => 'WS Rekam Medis',
        ];

        return [
            'id' => $moduleKey,
            'label' => $labels[$moduleKey] ?? strtoupper(str_replace('_', ' ', $moduleKey)),
            'items' => $items,
        ];
    }

    protected static function getDefaultValue(string $key): string
    {
        if (isset(self::$fieldDefaults[$key]) && self::$fieldDefaults[$key] !== null) {
            return (string) self::$fieldDefaults[$key];
        }

        if (str_contains($key, 'tgl') || str_contains($key, 'tanggal')) {
            return date('Y-m-d');
        }

        if (str_contains($key, 'bulan')) {
            return date('m');
        }

        if (str_contains($key, 'tahun')) {
            return date('Y');
        }

        if (str_contains($key, 'booking')) {
            return 'BK-' . date('Ymd') . '-001';
        }

        if (str_contains($key, 'kartu') || str_contains($key, 'noka')) {
            return '0001234567890';
        }

        if (str_contains($key, 'nik')) {
            return '3319022010810007';
        }

        if (str_contains($key, 'sep')) {
            return '0301R0110326V000001';
        }

        if (str_contains($key, 'poli')) {
            return 'ANA';
        }

        if (str_contains($key, 'dokter') || str_contains($key, 'dpjp')) {
            return '12345';
        }

        return '1';
    }

    protected static function cleanJsonPlaceholders(string $raw): string
    {
        $replacements = [
            '/\{kodebooking[^}]*\}/i' => 'BK-20260929-001',
            '/\{noka[^}]*\}|\{nomorkartu\}|\{no_kartu\}/i' => '0001234567890',
            '/\{nik[^}]*\}/i' => '3319022010810007',
            '/\{kodeppk\}|\{ppk[^}]*\}/i' => '0138U020',
            '/\{kodepoli\}|\{kdpoli\}/i' => 'ANA',
            '/\{kodedokter\}|\{kddpjp\}/i' => '12345',
            '/\{namapoli\}/i' => 'Poli Anak',
            '/\{namadokter\}/i' => 'dr. Budi Santoso, Sp.A',
            '/\{tgl[^}]*\}|\{tanggal[^}]*\}/i' => date('Y-m-d'),
            '/\{norm\}/i' => 'MR091823',
            '/\{nohp\}/i' => '081234567890',
            '/\{keterangan\}/i' => 'Pasien rujukan FKTP',
            '/\{jampraktek\}/i' => '08:00-12:00',
            '/\{1\(Ya\),0\(Tidak\)\}|\{1 \(Ya\), 0 \(Tidak\)\}/i' => '0',
            '/\{angka antrean\}/i' => '12',
            '/\{nomor antrean[^}]*\}/i' => 'A-012',
            '/\{waktu estimasi[^}]*\}/i' => '1727600000000',
            '/\{sisa kuota[^}]*\}/i' => '15',
            '/\{kuota[^}]*\}/i' => '30',
            '/\{norujukan[^}]*\}/i' => '0138U0200926P000001',
            '/\{1 \(Rujukan FKTP\)[^}]*\}/i' => '1',
            '/\{JKN \/ NON JKN\}/i' => 'JKN',
        ];

        return preg_replace(array_keys($replacements), array_values($replacements), $raw);
    }

    protected static function replaceJsonArrayPlaceholders(mixed $data): mixed
    {
        if (is_array($data)) {
            $cleaned = [];
            foreach ($data as $k => $v) {
                $cleaned[$k] = self::replaceJsonArrayPlaceholders($v);
            }
            return $cleaned;
        }

        if (is_string($data) && preg_match('/^\{([a-z0-9_\s\/-]+)\}$/i', $data, $m)) {
            $paramKey = strtolower(trim(preg_replace('/[^a-z0-9_]/i', '', $m[1])));
            return self::getDefaultValue($paramKey);
        }

        return $data;
    }

    protected static function generateDefaultRequestBody(string $module, string $endpoint, string $method): array
    {
        if ($module === 'icare') {
            return [
                'param' => '0001234567890',
                'kodedokter' => 12345,
            ];
        }

        if ($module === 'aplicares') {
            return [
                'kodekelas' => 'VIP',
                'koderuang' => 'RG01',
                'namaruang' => 'Ruang Anggrek VIP',
                'kapasitas' => 10,
                'tersedia' => 4,
                'tersediapria' => 2,
                'tersediawanita' => 2,
                'tersediapriawanita' => 0,
            ];
        }

        if ($module === 'antreanrs') {
            return [
                'kodebooking' => 'BK-' . date('Ymd') . '-001',
                'jenispasien' => 'JKN',
                'nomorkartu' => '0001234567890',
                'nik' => '3319022010810007',
                'nohp' => '081234567890',
                'kodepoli' => 'ANA',
                'namapoli' => 'Poli Anak',
                'pasienbaru' => 0,
                'norm' => 'MR091823',
                'tanggalperiksa' => date('Y-m-d'),
                'kodedokter' => 12345,
                'namadokter' => 'dr. Budi Santoso, Sp.A',
                'jampraktek' => '08:00-12:00',
                'jeniskunjungan' => 1,
                'nomorreferensi' => '0138U0200926P000001',
                'nomorantrean' => 'A-012',
                'angkaantrean' => 12,
                'estimasidilayani' => 1727600000000,
                'sisakuotajkn' => 15,
                'kuotajkn' => 30,
                'sisakuotanonjkn' => 10,
                'kuotanonjkn' => 20,
                'keterangan' => 'Pasien rujukan FKTP',
            ];
        }

        if ($module === 'apotek') {
            return [
                'noResep' => 'RSP-' . date('Ym') . '-001',
                'kodeObat' => '11250805294',
                'namaObat' => 'Paracetamol 500mg',
                'signa1' => 3,
                'signa2' => 1,
                'jumlah' => 10,
                'aturanPakai' => 'Sesudah Makan',
            ];
        }

        return [
            'request' => [
                't_transaksi' => [
                    'nomor' => '0001234567890',
                    'tanggal' => date('Y-m-d'),
                    'user' => 'SIMRS Admin',
                ],
            ],
        ];
    }

    protected static function ensureNonEmptyMockResponse(string $module, string $endpoint, string $method, ?array $existing): array
    {
        // If existing is already complete and not empty
        if ($existing !== null) {
            $resp = $existing['response'] ?? null;
            $hasData = !empty($resp) && (!is_array($resp) || count($resp) > 0) && (!is_object($resp) || count((array) $resp) > 0);
            if ($hasData) {
                return $existing;
            }
        }

        // Synthesize realistic mock data per module & endpoint
        $code = '200';
        $message = 'Sukses';
        $responseData = [];

        if ($module === 'icare') {
            $responseData = [
                'url' => 'https://jkn.bpjs-kesehatan.go.id/ihs/view/0001234567890/a8f9c1e7',
                'nomorKartu' => '0001234567890',
                'nama' => 'Budi Santoso',
                'statusPeserta' => 'AKTIF',
                'faskesTingkat1' => '0138U020 - KPRJ PALA MEDIKA',
            ];
        } elseif ($module === 'antreanrs' || $module === 'antreanfktp') {
            $code = '1';
            $message = 'Ok';
            $responseData = [
                'kodebooking' => 'BK-' . date('Ymd') . '-001',
                'nomorantrean' => 'A-012',
                'angkaantrean' => 12,
                'namapoli' => 'Poli Anak',
                'sisaantrean' => 3,
                'antreanpanggil' => 'A-009',
                'waktutunggu' => 600,
                'keterangan' => 'Pendaftaran antrean berhasil diproses',
            ];
        } elseif ($module === 'aplicares') {
            $code = '1';
            $message = 'OK';
            if (str_contains($endpoint, 'read') || str_contains($endpoint, 'kelas')) {
                $responseData = [
                    'list' => [
                        [
                            'kodekelas' => 'VIP',
                            'koderuang' => 'RG01',
                            'namaruang' => 'Ruang Anggrek VIP',
                            'kapasitas' => 10,
                            'tersedia' => 4,
                            'tersediapria' => 2,
                            'tersediawanita' => 2,
                            'tersediapriawanita' => 0,
                        ],
                        [
                            'kodekelas' => 'KL1',
                            'koderuang' => 'RG02',
                            'namaruang' => 'Ruang Melati Kelas 1',
                            'kapasitas' => 20,
                            'tersedia' => 8,
                            'tersediapria' => 4,
                            'tersediawanita' => 4,
                            'tersediapriawanita' => 0,
                        ],
                    ],
                ];
            } else {
                $responseData = [
                    'message' => 'Data ketersediaan tempat tidur kamar berhasil diperbarui',
                    'kodeppk' => '0138U020',
                    'koderuang' => 'RG01',
                    'tersedia' => 4,
                ];
            }
        } elseif ($module === 'apotek') {
            $responseData = [
                'noResep' => 'RSP-' . date('Ym') . '-0042',
                'tglPelayanan' => date('Y-m-d'),
                'kodeObat' => '11250805294',
                'namaObat' => 'Paracetamol 500mg',
                'jumlah' => 10,
                'status' => 'Berhasil Disimpan',
            ];
        } elseif ($module === 'pcare') {
            $responseData = [
                'noKunjungan' => '0138U0200926P000001',
                'noKartu' => '0001234567890',
                'nama' => 'M AMRU',
                'tglKunjungan' => date('Y-m-d'),
                'keluhan' => 'Demam dan pusing',
                'status' => 'Selesai',
            ];
        } else {
            // Default VClaim / other
            $responseData = [
                'noSep' => '0301R0110326V000001',
                'tglSep' => date('Y-m-d'),
                'jnsPelayanan' => 'Rawat Jalan',
                'peserta' => [
                    'noKartu' => '0001234567890',
                    'nama' => 'M AMRU',
                    'hakKelas' => 'Kelas 1',
                    'statusPeserta' => 'AKTIF',
                ],
                'status' => 'Sukses',
            ];
        }

        return [
            'metaData' => [
                'code' => $code,
                'message' => $message,
            ],
            'response' => $responseData,
        ];
    }

    private static function extractAllJsonBlocks(string $text): array
    {
        $blocks = [];
        $len = strlen($text);
        $inBrace = false;
        $depth = 0;
        $start = 0;

        for ($i = 0; $i < $len; $i++) {
            $ch = $text[$i];
            if ($ch === '{') {
                if ($depth === 0) {
                    $start = $i;
                    $inBrace = true;
                }
                $depth++;
            } elseif ($ch === '}') {
                $depth--;
                if ($inBrace && $depth === 0) {
                    $candidate = trim(substr($text, $start, $i - $start + 1));
                    $cleaned = self::cleanJsonPlaceholders($candidate);
                    if (json_validate($cleaned)) {
                        $blocks[] = $cleaned;
                    }
                    $inBrace = false;
                }
            }
        }

        return $blocks;
    }
}
