<?php

namespace Tests\Feature\Api\Concerns;

trait InteractsWithBpjsPayloads
{
    protected function sepInsertPayload(): array
    {
        return [
            'request' => [
                't_sep' => [
                    'noKartu' => '0001234567890',
                    'tglSep' => '2026-03-19',
                    'ppkPelayanan' => '0301R011',
                    'jnsPelayanan' => '2',
                    'klsRawat' => [
                        'klsRawatHak' => '3',
                        'klsRawatNaik' => '',
                        'pembiayaan' => '',
                        'penanggungJawab' => '',
                    ],
                    'noMR' => 'RM-001',
                    'rujukan' => [
                        'asalRujukan' => '1',
                        'tglRujukan' => '2026-03-18',
                        'noRujukan' => 'RJK-001',
                        'ppkRujukan' => '0301P001',
                    ],
                    'catatan' => 'Kontrol rawat jalan',
                    'diagAwal' => 'K30',
                    'poli' => [
                        'tujuan' => 'INT',
                        'eksekutif' => '0',
                    ],
                    'cob' => [
                        'cob' => '0',
                    ],
                    'katarak' => [
                        'katarak' => '0',
                    ],
                    'jaminan' => [
                        'lakaLantas' => '0',
                        'noLP' => '',
                        'penjamin' => [
                            'tglKejadian' => '',
                            'keterangan' => '',
                            'suplesi' => [
                                'suplesi' => '0',
                                'noSepSuplesi' => '',
                                'lokasiLaka' => [
                                    'kdPropinsi' => '',
                                    'kdKabupaten' => '',
                                    'kdKecamatan' => '',
                                ],
                            ],
                        ],
                    ],
                    'tujuanKunj' => '0',
                    'flagProcedure' => '',
                    'kdPenunjang' => '',
                    'assesmentPel' => '',
                    'skdp' => [
                        'noSurat' => '',
                        'kodeDPJP' => '',
                    ],
                    'dpjpLayan' => '12345',
                    'noTelp' => '081234567890',
                    'user' => 'tester',
                ],
            ],
        ];
    }

    protected function sepUpdatePayload(): array
    {
        return [
            'request' => [
                't_sep' => [
                    'noSep' => '0301R0110326V000001',
                    'user' => 'tester',
                ],
            ],
        ];
    }

    protected function sepDeletePayload(): array
    {
        return [
            'request' => [
                't_sep' => [
                    'noSep' => '0301R0110326V000001',
                    'user' => 'tester',
                ],
            ],
        ];
    }

    protected function suratKontrolInsertPayload(): array
    {
        return [
            'request' => [
                'noSEP' => '0301R0111018V000006',
                'kodeDokter' => '12345',
                'poliKontrol' => 'INT',
                'tglRencanaKontrol' => '2026-03-20',
                'user' => 'ws',
            ],
        ];
    }

    protected function suratKontrolUpdatePayload(): array
    {
        return [
            'request' => [
                'noSuratKontrol' => '0301R0110321K000002',
                'noSEP' => '0301R0111018V000006',
                'kodeDokter' => '12345',
                'poliKontrol' => 'INT',
                'tglRencanaKontrol' => '2026-03-20',
                'user' => 'ws',
            ],
        ];
    }

    protected function spriInsertPayload(): array
    {
        return [
            'request' => [
                'noKartu' => '0001234567890',
                'kodeDokter' => '12345',
                'poliKontrol' => 'INT',
                'tglRencanaKontrol' => '2026-03-20',
                'user' => 'ws',
            ],
        ];
    }

    protected function spriUpdatePayload(): array
    {
        return [
            'request' => [
                'noSPRI' => 'SPRI-001',
                'kodeDokter' => '12345',
                'poliKontrol' => 'INT',
                'tglRencanaKontrol' => '2026-03-20',
                'user' => 'ws',
            ],
        ];
    }
}
