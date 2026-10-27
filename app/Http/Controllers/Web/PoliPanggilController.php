<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Antrean;
use App\Services\Antrian\AntrianBridgingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PoliPanggilController extends Controller
{
    public function __construct(
        protected AntrianBridgingService $antrolService
    ) {}

    public function index(Request $request): Response
    {
        $today = Carbon::today()->toDateString();
        $kodePoli = $request->input('kodepoli', 'INT');

        $antreans = Antrean::where('tanggalperiksa', $today)
            ->where('kodepoli', $kodePoli)
            ->where('status', '!=', 'Batal')
            ->orderBy('angkaantrean', 'asc')
            ->get();

        $currentlyCalling = $antreans->firstWhere('status', 'Dipanggil Poli');

        return Inertia::render('Poli/Panggil', [
            'selectedPoli' => $kodePoli,
            'currentlyCalling' => $currentlyCalling,
            'antreans' => $antreans,
            'today' => now()->locale('id')->isoFormat('dddd, D MMMM Y'),
        ]);
    }

    public function panggil(Request $request, Antrean $antrean): RedirectResponse
    {
        $nowMs = (int) (microtime(true) * 1000);

        // Jika belum tercatat Task 4, catat Task 4 (Pasien mulai dilayani dokter)
        $update = [
            'status' => 'Dipanggil Poli',
        ];
        if (!$antrean->task4_waktu) {
            $update['task4_waktu'] = $nowMs;
        }

        $antrean->update($update);

        // Broadcast event ke Display TV
        Cache::put('antrol_last_call', [
            'id' => $antrean->id,
            'nomorantrean' => $antrean->nomorantrean,
            'namapoli' => $antrean->namapoli,
            'kodepoli' => $antrean->kodepoli,
            'namadokter' => $antrean->namadokter,
            'nama_pasien' => $antrean->nama_pasien,
            'timestamp' => $nowMs,
            'call_id' => uniqid('call_', true),
        ], 3600);

        // Kirim Task 4 ke BPJS Antrol
        try {
            $this->antrolService->updateWaktuAntrean($antrean->kode_booking, 4, $nowMs, 'Pasien dipanggil dokter poli')->exec();
        } catch (Throwable $e) {}

        return back()->with('success', "Memanggil antrean {$antrean->nomorantrean}");
    }

    public function selesai(Request $request, Antrean $antrean): RedirectResponse
    {
        $nowMs = (int) (microtime(true) * 1000);

        $antrean->update([
            'status' => 'Selesai Layanan Dokter',
            'task5_waktu' => $nowMs,
        ]);

        // Kirim Task 5 ke BPJS Antrol
        try {
            $this->antrolService->updateWaktuAntrean($antrean->kode_booking, 5, $nowMs, 'Pelayanan dokter poli selesai')->exec();
        } catch (Throwable $e) {}

        return back()->with('success', "Pelayanan dokter untuk {$antrean->nomorantrean} selesai.");
    }
}
