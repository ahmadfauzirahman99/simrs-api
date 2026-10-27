<?php

namespace App\Services\Antrian;

use App\Services\Bpjs\BaseBridgingV3;

class AntrianBridgingService extends BaseBridgingV3
{
    public function __construct()
    {
        parent::__construct();

        $this->baseUrl  = rtrim((string) config('services.bpjs_antrean.base_url', config('services.bpjs_v3.base_url')), '/');
        $this->cID      = (string) config('services.bpjs_antrean.cons_id', config('services.bpjs_v3.cons_id'));
        $this->cSecret  = (string) config('services.bpjs_antrean.secret_key', config('services.bpjs_v3.secret_key'));
        $this->user_key = (string) config('services.bpjs_antrean.user_key', config('services.bpjs_v3.user_key'));

        $this->refreshSecurityContext();
        $this->header['Content-Type'] = 'application/json';
    }

    protected function validateResponse(): bool
    {
        // Support both metaData and metadata (case insensitive)
        $meta = $this->response->metaData ?? $this->response->metadata ?? null;

        if (!$meta) {
            $this->errorCode = '500';
            $this->errorMessage = 'Tidak dapat menghubungkan dengan server BPJS Antrean';
            return false;
        }

        $code = (string) ($meta->code ?? '500');

        // BPJS Antrol uses code 1 or 200 for success
        if (in_array($code, ['1', '200'], true)) {
            $this->errorCode = null;
            $this->errorMessage = null;
            return true;
        }

        $this->errorCode = $code;
        $this->errorMessage = (string) ($meta->message ?? 'Terjadi kesalahan');

        return false;
    }

    public function refPoli(): static
    {
        $this->setUpUrl(['ref', 'poli']);
        return $this;
    }

    public function refDokter(): static
    {
        $this->setUpUrl(['ref', 'dokter']);
        return $this;
    }

    public function jadwalDokter(string $kodePoli, string $tanggal): static
    {
        $this->setUpUrl(['jadwaldokter', 'kodepoli', $kodePoli, 'tanggal', $tanggal]);
        return $this;
    }

    public function refPoliFp(): static
    {
        $this->setUpUrl(['ref', 'poli', 'fp']);
        return $this;
    }

    public function refPasienFp(string $jenis, string $noIdentitas): static
    {
        $this->setUpUrl(['ref', 'pasien', 'fp', 'identitas', strtolower($jenis), 'noidentitas', $noIdentitas]);
        return $this;
    }

    public function tambahAntrean(array $payload): static
    {
        $this->setUpUrl(['antrean', 'add'], json_encode($payload), 'POST');
        return $this;
    }

    public function updateWaktuAntrean(string $kodeBooking, int $taskId, int $waktuMs, ?string $keterangan = null): static
    {
        $payload = [
            'kodebooking' => $kodeBooking,
            'taskid' => $taskId,
            'waktu' => $waktuMs,
        ];

        if ($keterangan !== null) {
            $payload['keterangan'] = $keterangan;
        }

        $this->setUpUrl(['antrean', 'updatewaktu'], json_encode($payload), 'POST');
        return $this;
    }

    public function batalAntrean(string $kodeBooking, string $keterangan): static
    {
        $payload = [
            'kodebooking' => $kodeBooking,
            'keterangan' => $keterangan,
        ];

        $this->setUpUrl(['antrean', 'batal'], json_encode($payload), 'POST');
        return $this;
    }

    public function antreanPerKodeBooking(string $kodeBooking): static
    {
        $this->setUpUrl(['antrean', 'pendaftaran', 'kodebooking', $kodeBooking]);
        return $this;
    }

    public function antreanPerTanggal(string $tanggal): static
    {
        $this->setUpUrl(['antrean', 'pendaftaran', 'tanggal', $tanggal]);
        return $this;
    }
}
