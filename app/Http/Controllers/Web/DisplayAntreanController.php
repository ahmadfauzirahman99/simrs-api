<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Antrean;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class DisplayAntreanController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Display/Antrean', [
            'rsName' => config('app.name', 'HAURA MEDICAL'),
            'today' => now()->locale('id')->isoFormat('dddd, D MMMM Y'),
        ]);
    }

    public function state(Request $request): JsonResponse
    {
        $today = Carbon::today()->toDateString();
        $lastCall = Cache::get('antrol_last_call');

        // Master daftar poliklinik aktif di Haura Medical
        $masterPoli = [
            ['kodepoli' => 'INT', 'namapoli' => 'Poli Penyakit Dalam', 'namadokter' => 'dr. Budi Santoso, Sp.PD', 'ruang' => 'Ruang 101'],
            ['kodepoli' => 'ANA', 'namapoli' => 'Poli Anak', 'namadokter' => 'dr. Siti Aminah, Sp.A', 'ruang' => 'Ruang 102'],
            ['kodepoli' => 'BED', 'namapoli' => 'Poli Bedah Umum', 'namadokter' => 'dr. Hendra Wijaya, Sp.B', 'ruang' => 'Ruang 103'],
            ['kodepoli' => 'OBG', 'namapoli' => 'Poli Kebidanan & Kandungan', 'namadokter' => 'dr. Maya Sartika, Sp.OG', 'ruang' => 'Ruang 104'],
            ['kodepoli' => 'GIG', 'namapoli' => 'Poli Gigi & Mulut', 'namadokter' => 'drg. Rina Kusumawati', 'ruang' => 'Ruang 105'],
            ['kodepoli' => 'MAT', 'namapoli' => 'Poli Mata', 'namadokter' => 'dr. Anwar Fauzi, Sp.M', 'ruang' => 'Ruang 106'],
        ];

        // Ambil data antrean hari ini
        $todayAntreans = Antrean::where('tanggalperiksa', $today)->get();

        $activePoli = collect($masterPoli)->map(function ($poli) use ($todayAntreans) {
            $antreanPoli = $todayAntreans->where('kodepoli', $poli['kodepoli']);
            $current = $antreanPoli->firstWhere('status', 'Dipanggil Poli')
                ?? $antreanPoli->where('status', 'Selesai Layanan Dokter')->sortByDesc('updated_at')->first()
                ?? $antreanPoli->firstWhere('status', 'Check-In');

            return [
                'kodepoli' => $poli['kodepoli'],
                'namapoli' => $poli['namapoli'],
                'namadokter' => $current?->namadokter ?: $poli['namadokter'],
                'nomorantrean' => $current?->nomorantrean ?: '-',
                'status' => $current ? $current->status : 'Siap Melayani',
                'ruang' => $poli['ruang'],
                'sisa_antrean' => $antreanPoli->whereIn('status', ['Menunggu', 'Check-In'])->count(),
            ];
        })->values();

        $payload = [
            'last_call' => $lastCall,
            'active_poli' => $activePoli,
            'active_calls' => $activePoli,
            'running_text' => 'Selamat datang di HAURA MEDICAL. Pasien dimohon memperhatikan nomor antrean dan menuju ruang periksa saat dipanggil. Siapkan KTP/Identitas dan Kartu BPJS Kesehatan Anda. Layanan informasi: 0812-3456-7890.',
            'timestamp' => now()->toISOString(),
        ];

        return response()->json(array_merge([
            'success' => true,
            'data' => $payload,
        ], $payload));
    }
}
