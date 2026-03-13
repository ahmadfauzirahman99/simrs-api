<?php

namespace App\Services\Bpjs;

class BridgingV3 extends BaseBridgingV3
{
    public function queryGetPeserta(string $nomor, int $tipe = 1): static
    {
        if ($tipe === 1) {
            $this->setUpUrl(['Peserta', 'nokartu', $nomor, 'tglSEP', date('Y-m-d')]);
        } else {
            $this->setUpUrl(['Peserta', 'nik', $nomor, 'tglSEP', date('Y-m-d')]);
        }

        return $this;
    }

    public function cekKartu(string $no, string $tanggal): static
    {
        $this->setUpUrl(['Peserta', 'nokartu', $no, 'tglSEP', $tanggal]);
        return $this;
    }

    public function querySearchSEP(string $noSEP): static
    {
        $this->setUpUrl(['SEP', $noSEP]);
        return $this;
    }

    public function queryGetRiwayatSEP(string $noKartu): static
    {
        $this->setUpUrl(['SEP', 'Riwayat', $noKartu]);
        return $this;
    }

    public function queryInsertSEP(array $data): static
    {
        if (empty($data['request']['t_sep']['rujukan']['noRujukan'])) {
            $data['request']['t_sep']['rujukan']['noRujukan'] = '0';
        }

        $this->setUpUrl(['SEP', '2.0', 'insert'], json_encode($data), 'POST');
        return $this;
    }

    public function queryUpdateSEP(array $data): static
    {
        $this->setUpUrl(['SEP', '1.1', 'Update'], json_encode($data), 'PUT');
        return $this;
    }

    public function queryHapusSEP(array $data): static
    {
        $this->setUpUrl(['SEP', 'Delete'], json_encode($data), 'DELETE');
        return $this;
    }

    public function queryCheckOutSEP(array $data): static
    {
        $this->setUpUrl(['Sep', 'updtglplg'], json_encode($data), 'PUT');
        return $this;
    }

    public function pengajuanSEP(array $data): static
    {
        $this->setUpUrl(['Sep', 'pengajuanSEP'], json_encode($data), 'POST');
        return $this;
    }

    public function approvalSEP(array $data): static
    {
        $this->setUpUrl(['Sep', 'aprovalSEP'], json_encode($data), 'POST');
        return $this;
    }

    public function queryGetListPoli(string $poli): static
    {
        $this->setUpUrl(['referensi', 'poli', $poli]);
        return $this;
    }

    public function queryGetListDiagnosa(string $diagnosa): static
    {
        $this->setUpUrl(['referensi', 'diagnosa', $diagnosa]);
        return $this;
    }

    public function queryGetListFaskes(string $parameter, int $tipe = 1): static
    {
        $this->setUpUrl(['referensi', 'faskes', $parameter, $tipe]);
        return $this;
    }

    public function queryGetDokterDpjp(string $kode, string $jenis, string $tgl): static
    {
        $this->setUpUrl(['referensi', 'dokter', 'pelayanan', $jenis, 'tglPelayanan', $tgl, 'Spesialis', $kode]);
        return $this;
    }

    public function queryGetProvinsi(): static
    {
        $this->setUpUrl(['referensi', 'propinsi']);
        return $this;
    }

    public function queryGetKabupaten(string $prov): static
    {
        $this->setUpUrl(['referensi', 'kabupaten', 'propinsi', $prov]);
        return $this;
    }

    public function queryGetKecamatan(string $kab): static
    {
        $this->setUpUrl(['referensi', 'kecamatan', 'kabupaten', $kab]);
        return $this;
    }

    public function getProsedur(string $data): static
    {
        $this->setUpUrl(['referensi', 'procedure', $data], [], 'GET');
        return $this;
    }

    public function queryHistoryPelayananPeserta(string $nokartu, ?string $tglawal = null, ?string $tglakhir = null): static
    {
        $tglawal = $tglawal ?: date('Y-m-d', strtotime('-90 day'));
        $tglakhir = $tglakhir ?: date('Y-m-d');

        $this->setUpUrl([
            'monitoring',
            'HistoriPelayanan',
            'NoKartu',
            $nokartu,
            'tglMulai',
            $tglawal,
            'tglAkhir',
            $tglakhir
        ]);

        return $this;
    }

    public function monitoringKunjungan(string $tanggal, string $tipe): static
    {
        $this->setUpUrl([
            'Monitoring',
            'Kunjungan',
            'Tanggal',
            date('Y-m-d', strtotime($tanggal)),
            'JnsPelayanan',
            $tipe
        ]);

        return $this;
    }

    public function monitoringKlaim(string $tanggal, string $tipe, string $status): static
    {
        $this->setUpUrl([
            'Monitoring',
            'Klaim',
            'Tanggal',
            date('Y-m-d', strtotime($tanggal)),
            'JnsPelayanan',
            $tipe,
            'Status',
            $status
        ]);

        return $this;
    }

    public function queryListKontrol(): static
    {
        $this->setUpUrl([
            'RencanaKontrol',
            'ListRencanaKontrol',
            'tglAwal',
            date('Y-m-d', strtotime('-29 day')),
            'tglAkhir',
            date('Y-m-d'),
            'filter',
            '1'
        ]);

        return $this;
    }

    public function querySuratKontrol(string $noSuratKontrol): static
    {
        $this->setUpUrl(['RencanaKontrol', 'noSuratKontrol', $noSuratKontrol]);
        return $this;
    }

    public function queryCarisepSuratKontrol(string $noSep): static
    {
        $this->setUpUrl(['RencanaKontrol', 'nosep', $noSep]);
        return $this;
    }

    public function insertSuratKontrol(array $data): static
    {
        $this->setUpUrl(['RencanaKontrol', 'insert'], json_encode($data), 'POST');
        return $this;
    }


    public function insertSpri(array $data): static
    {
        $this->setUpUrl(['RencanaKontrol', 'insertSPRI'], json_encode($data), 'POST');
        return $this;
    }

     public function updateSpri(array $data): static
    {
        $this->setUpUrl(['RencanaKontrol', 'updateSPRI'], json_encode($data), 'POST');
        return $this;
    }
    public function updateSuratKontrol(array $data): static
    {
        $this->setUpUrl(['RencanaKontrol', 'Update'], json_encode($data), 'PUT');
        return $this;
    }

    public function deleteSuratKontrol(array $data): static
    {
        $this->setUpUrl(['RencanaKontrol', 'Delete'], json_encode($data), 'DELETE');
        return $this;
    }

    public function queryMultiRujukan(string $nomor, int $tipe = 1): static
    {
        $param = ['Rujukan'];

        if ($tipe === 2) {
            $param[] = 'RS';
        }

        $param[] = 'List';
        $param[] = 'Peserta';
        $param[] = $nomor;

        $this->setUpUrl($param);
        return $this;
    }

    public function queryGetRujukan(string $parameter, int $faskes = 1): static
    {
        $param = ['Rujukan'];

        if ($faskes === 2) {
            $param[] = 'Rs';
        }

        $param[] = $parameter;

        $this->setUpUrl($param);
        return $this;
    }

    public function getDataJumlahSepRujukan(string $noRujukan, string $jenis = '1'): static
    {
        $this->setUpUrl([
            'Rujukan',
            'JumlahSEP',
            $jenis,
            $noRujukan
        ]);

        return $this;
    }
}
