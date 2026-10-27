<?php

namespace App\Console\Commands;

use App\Models\Antrean;
use App\Services\Antrian\AntrianBridgingService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

class SyncLocalSimrsToBpjsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'simrs:sync-antrol
                            {--date= : Tanggal periksa YYYY-MM-DD (default: hari ini)}
                            {--daemon : Jalankan terus-menerus di background (polling agent)}
                            {--interval=10 : Interval polling dalam detik jika mode daemon}
                            {--dry-run : Pratinjau aliran task tanpa mengirim ke BPJS}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi antrean & timestamp Task ID 1-7 dari database lokal SIMRS ke BPJS Antrol';

    public function handle(AntrianBridgingService $antrianService): int
    {
        $this->info("===============================================================");
        $this->info("   SIMRS GATEWAY — BPJS ANTREAN ONLINE SYNC AGENT v2.0         ");
        $this->info("===============================================================");

        $isDaemon = (bool) $this->option('daemon');
        $isDryRun = (bool) $this->option('dry-run');
        $interval = max(3, (int) $this->option('interval'));
        $targetDate = $this->option('date') ?: Carbon::today()->toDateString();

        $this->comment("Target Tanggal : {$targetDate}");
        $this->comment("Mode Eksekusi  : " . ($isDaemon ? "Daemon Polling ({$interval}s)" : "Single Pass"));
        if ($isDryRun) {
            $this->warn("PERINGATAN     : Mode Dry-Run Aktif (Simulasi, tidak mengirim ke BPJS)");
        }
        $this->newLine();

        do {
            $this->processSync($targetDate, $isDryRun, $antrianService);

            if ($isDaemon) {
                $this->line("<fg=gray>[" . now()->toTimeString() . "] Menunggu {$interval} detik untuk batch berikutnya...</>");
                sleep($interval);
            }
        } while ($isDaemon);

        $this->info("Sinkronisasi SIMRS ke BPJS Antrol selesai.");
        return Command::SUCCESS;
    }

    protected function processSync(string $targetDate, bool $isDryRun, AntrianBridgingService $antrianService): void
    {
        // Ambil antrean pada tanggal target yang berstatus JKN
        $antreans = Antrean::where('tanggalperiksa', $targetDate)
            ->where('jenispasien', 'JKN')
            ->orderBy('id', 'asc')
            ->get();

        if ($antreans->isEmpty()) {
            $this->line("<fg=gray>[" . now()->toTimeString() . "] Tidak ada antrean pasien JKN ditemukan pada {$targetDate}.</>");
            return;
        }

        $totalTasksUpdated = 0;

        foreach ($antreans as $antrean) {
            // Task Map: Task ID => Timestamp Column (milliseconds)
            $tasks = [
                1 => $antrean->task1_waktu,
                2 => $antrean->task2_waktu,
                3 => $antrean->task3_waktu,
                4 => $antrean->task4_waktu,
                5 => $antrean->task5_waktu,
                6 => $antrean->task6_waktu,
                7 => $antrean->task7_waktu,
            ];

            foreach ($tasks as $taskId => $waktuMs) {
                if (empty($waktuMs)) {
                    continue;
                }

                $readableTime = date('Y-m-d H:i:s', (int) ($waktuMs / 1000));

                if ($isDryRun) {
                    $this->line(" [DRY-RUN] Booking: <fg=cyan>{$antrean->kode_booking}</> | Task {$taskId} | Waktu: {$readableTime} ({$waktuMs}ms)");
                    $totalTasksUpdated++;
                    continue;
                }
                try {
                    $result = $antrianService->updateWaktuAntrean(
                        kodebooking: $antrean->kode_booking,
                        taskid: $taskId,
                        waktu: $waktuMs,
                        jenispasien: $antrean->jenispasien ?: 'JKN'
                    );

                    $code = $result['metadata']['code'] ?? 500;
                    $message = $result['metadata']['message'] ?? 'OK';

                    if (in_array((int)$code, [200, 201])) {
                        $this->line(" <fg=green>✓</> Booking: <fg=cyan>{$antrean->kode_booking}</> | Task {$taskId} terkirim ke BPJS. [{$code} - {$message}]");
                    } else {
                        $this->line(" <fg=yellow>!</> Booking: <fg=cyan>{$antrean->kode_booking}</> | Task {$taskId}: [{$code} - {$message}]");
                    }

                    $totalTasksUpdated++;
                } catch (Throwable $e) {
                    $this->error(" ✗ Gagal kirim Task {$taskId} untuk {$antrean->kode_booking}: " . $e->getMessage());
                }
            }
        }

        $this->info("[" . now()->toTimeString() . "] Diproses: {$antreans->count()} antrean, {$totalTasksUpdated} task diperbarui.");
    }
}
