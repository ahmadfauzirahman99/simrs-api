<?php

namespace App\Services\Bpjs\MedicalRecord;

class BuildBpjsMedicalRecordRequestService
{
    public function build(string $noSep, string $jnsPelayanan, string $bulan, string $tahun, string $dataMr): array
    {
        return [
            'request' => [
                'noSep' => $noSep,
                'jnsPelayanan' => $jnsPelayanan,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'dataMR' => $dataMr,
            ],
        ];
    }
}
