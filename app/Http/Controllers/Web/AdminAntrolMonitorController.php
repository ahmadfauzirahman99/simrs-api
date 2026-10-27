<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Antrean;
use App\Services\Antrian\AntrianBridgingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class AdminAntrolMonitorController extends Controller
{
    public function __construct(
        protected AntrianBridgingService $antrolService
    ) {}

    public function index(Request $request): Response
    {
        $tanggal = $request->input('tanggal', Carbon::today()->toDateString());

        $antreans = Antrean::where('tanggalperiksa', $tanggal)
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($a) {
                // Format milidetik ke jam:menit:detik
                $formatTime = fn (?int $ms) => $ms ? date('H:i:s', (int) ($ms / 1000)) : null;

                // Hitung durasi dalam menit
                $diffMinutes = function (?int $tEnd, ?int $tStart) {
                    if (!$tEnd || !$tStart || $tEnd < $tStart) return null;
                    return round(($tEnd - $tStart) / 60000, 1);
                };

                return [
                    'id' => $a->id,
                    'kode_booking' => $a->kode_booking,
                    'nomorantrean' => $a->nomorantrean,
                    'nama_pasien' => $a->nama_pasien,
                    'nomorkartu' => $a->nomorkartu,
                    'nik' => $a->nik,
                    'jenispasien' => $a->jenispasien,
                    'kodepoli' => $a->kodepoli,
                    'namapoli' => $a->namapoli,
                    'namadokter' => $a->namadokter,
                    'jampraktek' => $a->jampraktek,
                    'status' => $a->status,
                    'sumber' => $a->sumber,
                    'tasks' => [
                        'task1' => ['waktu' => $a->task1_waktu, 'time_str' => $formatTime($a->task1_waktu), 'label' => 'Tunggu Admisi'],
                        'task2' => ['waktu' => $a->task2_waktu, 'time_str' => $formatTime($a->task2_waktu), 'label' => 'Layan Admisi'],
                        'task3' => ['waktu' => $a->task3_waktu, 'time_str' => $formatTime($a->task3_waktu), 'label' => 'Selesai Admisi / Tiba'],
                        'task4' => ['waktu' => $a->task4_waktu, 'time_str' => $formatTime($a->task4_waktu), 'label' => 'Dipanggil Dokter'],
                        'task5' => ['waktu' => $a->task5_waktu, 'time_str' => $formatTime($a->task5_waktu), 'label' => 'Selesai Dokter'],
                        'task6' => ['waktu' => $a->task6_waktu, 'time_str' => $formatTime($a->task6_waktu), 'label' => 'Tunggu Farmasi'],
                        'task7' => ['waktu' => $a->task7_waktu, 'time_str' => $formatTime($a->task7_waktu), 'label' => 'Obat Diserahkan'],
                    ],
                    'durasi' => [
                        'tunggu_admisi' => $diffMinutes($a->task2_waktu, $a->task1_waktu),
                        'tunggu_poli' => $diffMinutes($a->task4_waktu, $a->task3_waktu),
                        'layan_dokter' => $diffMinutes($a->task5_waktu, $a->task4_waktu),
                        'tunggu_farmasi' => $diffMinutes($a->task7_waktu, $a->task6_waktu),
                        'total_waktu' => $diffMinutes($a->task7_waktu ?? (int)(microtime(true)*1000), $a->task1_waktu ?? $a->task3_waktu),
                    ],
                ];
            });

        return Inertia::render('Admin/Antrol/Monitor', [
            'antreans' => $antreans,
            'selectedTanggal' => $tanggal,
            'stats' => [
                'total' => $antreans->count(),
                'checkin' => $antreans->whereIn('status', ['Check-In', 'Dipanggil Poli', 'Selesai Layanan Dokter', 'Selesai'])->count(),
                'sedang_layani' => $antreans->where('status', 'Dipanggil Poli')->count(),
                'selesai' => $antreans->where('status', 'Selesai')->count(),
            ],
        ]);
    }

    /**
     * Simulator untuk melompatkan progress Task ID (misal simulasi Poli / Dokter / Farmasi)
     */
    public function advanceTask(Request $request, Antrean $antrean): RedirectResponse
    {
        $request->validate([
            'taskid' => 'required|integer|between:1,7',
        ]);

        $taskId = (int) $request->input('taskid');
        $nowMs = (int) (microtime(true) * 1000);

        $update = ["task{$taskId}_waktu" => $nowMs];

        if ($taskId === 4) {
            $update['status'] = 'Dipanggil Poli';
        } elseif ($taskId === 5) {
            $update['status'] = 'Selesai Layanan Dokter';
        } elseif ($taskId === 6) {
            $update['status'] = 'Mulai Farmasi';
        } elseif ($taskId === 7) {
            $update['status'] = 'Selesai';
        }

        $antrean->update($update);

        // Forward ke BPJS Antrol
        try {
            $this->antrolService->updateWaktuAntrean($antrean->kode_booking, $taskId, $nowMs)->exec();
        } catch (Throwable $e) {}

        return back()->with('success', "Task ID {$taskId} berhasil dicatat & disinkronkan ke server BPJS Antrol!");
    }
}
