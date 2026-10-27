<?php

namespace App\Http\Controllers\Api\Apm;

use App\Http\Controllers\Controller;
use App\Models\Antrean;
use App\Services\Antrian\AntrianBridgingService;
use App\Services\Bpjs\BridgingV3;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Throwable;

class ApmController extends Controller
{
    public function __construct(
        protected AntrianBridgingService $antrolService,
        protected BridgingV3 $vclaimService
    ) {}

    /**
     * Cek Peserta BPJS & Kelayakan Kunjungan (Rujukan / Surat Kontrol / Finger Print)
     */
    public function searchPeserta(Request $request): JsonResponse
    {
        $nomor = trim((string) $request->input('nomor'));
        $tipe = (int) $request->input('tipe', strlen($nomor) === 16 ? 2 : 1); // 1 = No Kartu, 2 = NIK

        if (empty($nomor)) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor kartu atau NIK wajib diisi',
            ], 422);
        }

        $peserta = null;
        $rujukan = [];
        $fingerprint = null;

        // 1. Cek Peserta VClaim
        $vclaimConnected = false;
        try {
            if ($this->vclaimService->queryGetPeserta($nomor, $tipe)->exec()) {
                $peserta = $this->vclaimService->getResponse()->peserta ?? null;
                $vclaimConnected = true;
            }
        } catch (Throwable $e) {
            // Log fallback
        }

        // Jika bridge VClaim offline / simulasi dev, sediakan dummy bila nomor test
        if (!$peserta) {
            return response()->json([
                'success' => false,
                'message' => $this->vclaimService->errorMessage ?? 'Data peserta BPJS tidak ditemukan atau server BPJS offline',
                'raw_error' => $this->vclaimService->getError(),
            ], 404);
        }

        $noKartu = $peserta->noKartu ?? $nomor;
        $nik = $peserta->nik ?? $nomor;

        // 2. Cek Riwayat Rujukan FKTP (Faskes 1)
        try {
            if ($this->vclaimService->queryListRujukanFaskes1($noKartu)->exec()) {
                $resRujukan = $this->vclaimService->getResponse();
                $rujukan = $resRujukan->rujukan ?? [];
            }
        } catch (Throwable) {}

        // 3. Cek Status Finger Print
        try {
            if ($this->antrolService->refPasienFp('nik', $nik)->exec()) {
                $fingerprint = $this->antrolService->getResponse();
            }
        } catch (Throwable) {}

        return response()->json([
            'success' => true,
            'message' => 'Data peserta ditemukan',
            'data' => [
                'peserta' => $peserta,
                'rujukan' => $rujukan,
                'fingerprint' => $fingerprint,
            ],
        ]);
    }

    /**
     * Jadwal Dokter & Poliklinik yang Buka Hari Ini
     */
    public function jadwalHariIni(Request $request): JsonResponse
    {
        $tanggal = $request->input('tanggal', Carbon::today()->format('Y-m-d'));
        $kodePoli = $request->input('kodepoli');

        // Sediakan katalog poli standar bila offline atau BPJS Antrol belum terkonfigurasi
        $defaultPoli = [
            ['kdpoli' => 'INT', 'nmpoli' => 'PENYAKIT DALAM', 'dokter' => [
                ['kodedokter' => '101', 'namadokter' => 'dr. Budi Santoso, Sp.PD', 'jampraktek' => '08:00-12:00', 'kuota' => 30],
                ['kodedokter' => '102', 'namadokter' => 'dr. Siti Rahma, Sp.PD', 'jampraktek' => '13:00-16:00', 'kuota' => 25],
            ]],
            ['kdpoli' => 'ANA', 'nmpoli' => 'ANAK', 'dokter' => [
                ['kodedokter' => '201', 'namadokter' => 'dr. Hendra Wijaya, Sp.A', 'jampraktek' => '08:30-12:30', 'kuota' => 25],
            ]],
            ['kdpoli' => 'BED', 'nmpoli' => 'BEDAH UMUM', 'dokter' => [
                ['kodedokter' => '301', 'namadokter' => 'dr. Agus Supriatna, Sp.B', 'jampraktek' => '09:00-13:00', 'kuota' => 20],
            ]],
            ['kdpoli' => 'OBG', 'nmpoli' => 'KANDUNGAN & KEBIDANAN', 'dokter' => [
                ['kodedokter' => '401', 'namadokter' => 'dr. Maya Farida, Sp.OG', 'jampraktek' => '08:00-11:30', 'kuota' => 20],
            ]],
            ['kdpoli' => 'MAT', 'nmpoli' => 'MATA', 'dokter' => [
                ['kodedokter' => '501', 'namadokter' => 'dr. Dian Pratama, Sp.M', 'jampraktek' => '09:00-12:00', 'kuota' => 25],
            ]],
            ['kdpoli' => 'GIG', 'nmpoli' => 'GIGI & MULUT', 'dokter' => [
                ['kodedokter' => '601', 'namadokter' => 'drg. Tri Wahyuni, Sp.KG', 'jampraktek' => '08:00-12:00', 'kuota' => 15],
            ]],
        ];

        // Hitung sisa kuota berdasarkan antrean yang sudah terdaftar hari ini
        foreach ($defaultPoli as &$poli) {
            foreach ($poli['dokter'] as &$dok) {
                $terdaftar = Antrean::where('tanggalperiksa', $tanggal)
                    ->where('kodepoli', $poli['kdpoli'])
                    ->where('kodedokter', (string) $dok['kodedokter'])
                    ->where('status', '!=', 'Batal')
                    ->count();

                $dok['terdaftar'] = $terdaftar;
                $dok['sisa_kuota'] = max(0, $dok['kuota'] - $terdaftar);
            }
        }

        if ($kodePoli) {
            $filtered = array_values(array_filter($defaultPoli, fn($p) => $p['kdpoli'] === strtoupper($kodePoli)));
            return response()->json([
                'success' => true,
                'data' => $filtered,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $defaultPoli,
        ]);
    }

    /**
     * Check-In Kode Booking (Mobile JKN / Web RS) di Layar APM Kiosk
     */
    public function checkIn(Request $request): JsonResponse
    {
        $request->validate([
            'kodebooking' => 'required|string',
        ]);

        $kodeBooking = trim($request->input('kodebooking'));
        $today = Carbon::today()->toDateString();
        $nowMs = (int) (microtime(true) * 1000);

        $antrean = Antrean::where('kode_booking', $kodeBooking)->first();

        if (!$antrean) {
            // Bila antrean di-booking via Mobile JKN dan belum ada di DB lokal,
            // query ke BPJS Antrol
            try {
                if ($this->antrolService->antreanPerKodeBooking($kodeBooking)->exec()) {
                    $resp = $this->antrolService->getResponse();
                    $list = is_array($resp) ? $resp : ($resp->list ?? [$resp]);
                    if (!empty($list[0])) {
                        $item = (object) $list[0];
                        $antrean = Antrean::create([
                            'kode_booking' => $kodeBooking,
                            'jenispasien' => ($item->ispeserta ?? 1) ? 'JKN' : 'NON JKN',
                            'nomorkartu' => $item->nokapst ?? null,
                            'nik' => $item->nik ?? null,
                            'nama_pasien' => $item->nama ?? 'Pasien Mobile JKN',
                            'nohp' => $item->nohp ?? null,
                            'kodepoli' => $item->kodepoli ?? 'UMU',
                            'namapoli' => $item->namapoli ?? 'POLIKLINIK',
                            'kodedokter' => (string) ($item->kodedokter ?? '0'),
                            'namadokter' => $item->namadokter ?? 'Dokter Spesialis',
                            'tanggalperiksa' => $today,
                            'jampraktek' => $item->jampraktek ?? '08:00-12:00',
                            'jeniskunjungan' => $item->jeniskunjungan ?? 1,
                            'nomorreferensi' => $item->nomorreferensi ?? null,
                            'nomorantrean' => $item->noantrean ?? 'JKN-001',
                            'angkaantrean' => (int) filter_var($item->noantrean ?? 1, FILTER_SANITIZE_NUMBER_INT) ?: 1,
                            'estimasidilayani' => $item->estimasidilayani ?? ($nowMs + 900000),
                            'status' => 'Menunggu',
                            'sumber' => 'Mobile JKN',
                        ]);
                    }
                }
            } catch (Throwable $e) {}
        }

        if (!$antrean) {
            return response()->json([
                'success' => false,
                'message' => "Kode booking [{$kodeBooking}] tidak ditemukan.",
            ], 404);
        }

        if ($antrean->tanggalperiksa !== $today) {
            return response()->json([
                'success' => false,
                'message' => "Check-in hanya berlaku di hari periksa. Tanggal periksa Anda: {$antrean->tanggalperiksa}",
            ], 422);
        }

        if ($antrean->status === 'Check-In' || $antrean->task3_waktu) {
            return response()->json([
                'success' => true,
                'message' => 'Anda sudah melakukan check-in sebelumnya.',
                'data' => $antrean,
                'already_checked_in' => true,
            ]);
        }

        // Kirim Task ID 3 ke BPJS Antrean Online (Waktu pasien tiba di RS / tunggu poli)
        $bpjsSuccess = false;
        try {
            if ($this->antrolService->updateWaktuAntrean($kodeBooking, 3, $nowMs, 'Pasien Check-In Mandiri di Kiosk APM')->exec()) {
                $bpjsSuccess = true;
            }
        } catch (Throwable $e) {}

        // Update database lokal
        $antrean->update([
            'status' => 'Check-In',
            'task3_waktu' => $nowMs,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Check-in berhasil! Silakan ambil tiket antrean poli Anda.',
            'bpjs_antrol_synced' => $bpjsSuccess,
            'data' => $antrean,
        ]);
    }

    /**
     * Pendaftaran Mandiri Pasien di Kiosk APM (Go-Show)
     */
    public function daftarMandiri(Request $request): JsonResponse
    {
        $request->validate([
            'jenispasien' => 'required|in:JKN,NON JKN',
            'kodepoli' => 'required|string',
            'namapoli' => 'required|string',
            'kodedokter' => 'required',
            'namadokter' => 'required|string',
            'jampraktek' => 'nullable|string',
            'nomorkartu' => 'nullable|string',
            'nik' => 'nullable|string',
            'nama_pasien' => 'required|string',
            'nohp' => 'nullable|string',
            'jeniskunjungan' => 'nullable|integer',
            'nomorreferensi' => 'nullable|string',
            'norm' => 'nullable|string',
        ]);

        $today = Carbon::today()->toDateString();
        $nowMs = (int) (microtime(true) * 1000);

        // Generate Kode Booking unik
        $kodeBooking = 'APM' . Carbon::today()->format('ymd') . strtoupper(Str::random(4));

        // Hitung antrean poli hari ini
        $urutan = Antrean::where('tanggalperiksa', $today)
            ->where('kodepoli', $request->kodepoli)
            ->where('status', '!=', 'Batal')
            ->count() + 1;

        $nomorAntrean = strtoupper($request->kodepoli) . '-' . str_pad((string) $urutan, 3, '0', STR_PAD_LEFT);
        $estimasiLayan = $nowMs + ($urutan * 10 * 60 * 1000); // 10 menit per nomor

        $antrean = Antrean::create([
            'kode_booking' => $kodeBooking,
            'jenispasien' => $request->jenispasien,
            'nomorkartu' => $request->nomorkartu,
            'nik' => $request->nik,
            'nama_pasien' => $request->nama_pasien,
            'nohp' => $request->nohp,
            'kodepoli' => $request->kodepoli,
            'namapoli' => $request->namapoli,
            'kodedokter' => (string) $request->kodedokter,
            'namadokter' => $request->namadokter,
            'pasienbaru' => (bool) $request->input('pasienbaru', false),
            'norm' => $request->norm,
            'tanggalperiksa' => $today,
            'jampraktek' => $request->jampraktek ?? '08:00-12:00',
            'jeniskunjungan' => (int) ($request->jeniskunjungan ?? 1),
            'nomorreferensi' => $request->nomorreferensi,
            'nomorantrean' => $nomorAntrean,
            'angkaantrean' => $urutan,
            'estimasidilayani' => $estimasiLayan,
            'sisakuotajkn' => max(0, 30 - $urutan),
            'kuotajkn' => 30,
            'status' => 'Check-In',
            'sumber' => 'APM',
            'task1_waktu' => $nowMs,
            'task2_waktu' => $nowMs + 30000,
            'task3_waktu' => $nowMs + 60000,
        ]);

        // Kirim Tambah Antrean ke BPJS jika pasien JKN
        $bpjsSynced = false;
        if ($request->jenispasien === 'JKN') {
            try {
                $bpjsPayload = [
                    'kodebooking' => $kodeBooking,
                    'jenispasien' => 'JKN',
                    'nomorkartu' => $request->nomorkartu ?? '',
                    'nik' => $request->nik ?? '',
                    'nohp' => $request->nohp ?? '08123456789',
                    'kodepoli' => $request->kodepoli,
                    'namapoli' => $request->namapoli,
                    'pasienbaru' => $request->input('pasienbaru', 0) ? 1 : 0,
                    'norm' => $request->norm ?? '000001',
                    'tanggalperiksa' => $today,
                    'kodedokter' => (int) $request->kodedokter,
                    'namadokter' => $request->namadokter,
                    'jampraktek' => $request->jampraktek ?? '08:00-12:00',
                    'jeniskunjungan' => (int) ($request->jeniskunjungan ?? 1),
                    'nomorreferensi' => $request->nomorreferensi ?? '',
                    'nomorantrean' => $nomorAntrean,
                    'angkaantrean' => $urutan,
                    'estimasidilayani' => $estimasiLayan,
                    'sisakuotajkn' => max(0, 30 - $urutan),
                    'kuotajkn' => 30,
                    'sisakuotanonjkn' => 10,
                    'kuotanonjkn' => 10,
                    'keterangan' => 'Pendaftaran melalui APM Mandiri RS',
                ];

                if ($this->antrolService->tambahAntrean($bpjsPayload)->exec()) {
                    $bpjsSynced = true;
                    // Trigger Task 1, 2, 3 ke BPJS Antrol
                    $this->antrolService->updateWaktuAntrean($kodeBooking, 1, $nowMs)->exec();
                    $this->antrolService->updateWaktuAntrean($kodeBooking, 2, $nowMs + 30000)->exec();
                    $this->antrolService->updateWaktuAntrean($kodeBooking, 3, $nowMs + 60000)->exec();
                }
            } catch (Throwable $e) {}
        }

        return response()->json([
            'success' => true,
            'message' => 'Pendaftaran berhasil. Silakan simpan nomor antrean Anda.',
            'bpjs_antrol_synced' => $bpjsSynced,
            'data' => $antrean,
        ]);
    }

    /**
     * Update Task ID Waktu Layanan (Poli / Dokter / Farmasi)
     */
    public function updateTask(Request $request): JsonResponse
    {
        $request->validate([
            'kodebooking' => 'required|string',
            'taskid' => 'required|integer|between:1,7',
        ]);

        $kodeBooking = $request->input('kodebooking');
        $taskId = (int) $request->input('taskid');
        $nowMs = (int) (microtime(true) * 1000);

        $antrean = Antrean::where('kode_booking', $kodeBooking)->first();

        if (!$antrean) {
            return response()->json([
                'success' => false,
                'message' => 'Kode booking tidak ditemukan',
            ], 404);
        }

        $field = "task{$taskId}_waktu";
        $updateData = [$field => $nowMs];

        if ($taskId === 4) {
            $updateData['status'] = 'Dipanggil Poli';
        } elseif ($taskId === 5) {
            $updateData['status'] = 'Selesai Layanan Dokter';
        } elseif ($taskId === 7) {
            $updateData['status'] = 'Selesai';
        }

        $antrean->update($updateData);

        // Forward ke BPJS Antrol
        $bpjsSynced = false;
        try {
            if ($this->antrolService->updateWaktuAntrean($kodeBooking, $taskId, $nowMs)->exec()) {
                $bpjsSynced = true;
            }
        } catch (Throwable $e) {}

        return response()->json([
            'success' => true,
            'message' => "Task ID {$taskId} berhasil diupdate",
            'bpjs_antrol_synced' => $bpjsSynced,
            'data' => $antrean,
        ]);
    }

    /**
     * Monitoring Status Antrean Hari Ini
     */
    public function statusAntrean(Request $request): JsonResponse
    {
        $today = Carbon::today()->toDateString();

        $antreans = Antrean::where('tanggalperiksa', $today)
            ->where('status', '!=', 'Batal')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'tanggal' => $today,
            'total_antrean' => $antreans->count(),
            'total_checkin' => $antreans->where('status', 'Check-In')->count(),
            'total_selesai' => $antreans->where('status', 'Selesai')->count(),
            'data' => $antreans,
        ]);
    }
}
