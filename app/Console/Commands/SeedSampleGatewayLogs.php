<?php

namespace App\Console\Commands;

use App\Models\GatewayLog;
use App\Models\User;
use Illuminate\Console\Command;

class SeedSampleGatewayLogs extends Command
{
    protected $signature = 'simrs:seed-logs';
    protected $description = 'Seed sample realistic gateway logs for testing dashboard telemetry';

    public function handle(): int
    {
        $user4 = User::find(4);
        $user3 = User::find(3);

        $endpoints = [
            ['/api/v1/bpjs/peserta', 'GET', 'BPJS VClaim', 200, 185],
            ['/api/v1/bpjs/sep', 'POST', 'BPJS VClaim', 200, 342],
            ['/api/v1/bpjs/referensi/poli', 'GET', 'BPJS Antrean', 200, 140],
            ['/api/v1/bpjs/referensi/dokter', 'GET', 'BPJS Antrean', 200, 160],
            ['/api/v1/bpjs/rencana-kontrol/insert', 'POST', 'BPJS VClaim', 200, 290],
            ['/api/v1/bpjs/peserta', 'GET', 'BPJS VClaim', 200, 195],
            ['/api/v1/bpjs/sep', 'POST', 'BPJS VClaim', 422, 110],
            ['/api/v1/bpjs/monitoring/kunjungan', 'GET', 'BPJS VClaim', 200, 420],
            ['/api/v1/bpjs/rujukan/12345678', 'GET', 'BPJS VClaim', 200, 210],
            ['/api/v1/satusehat/Encounter', 'POST', 'SATUSEHAT FHIR', 200, 380],
        ];

        $now = now();
        for ($i = 0; $i < 28; $i++) {
            $ep = $endpoints[array_rand($endpoints)];
            $targetUser = ($i % 3 === 0) ? $user3 : $user4;
            $timeOffsetMinutes = rand(5, 520);
            $createdAt = $now->copy()->subMinutes($timeOffsetMinutes);

            GatewayLog::create([
                'user_id' => $targetUser ? $targetUser->id : null,
                'client_name' => $targetUser ? $targetUser->name : 'Faskes Mitra',
                'client_email' => $targetUser ? $targetUser->email : 'client@mitra.id',
                'endpoint' => $ep[0],
                'method' => $ep[1],
                'service' => $ep[2],
                'status_code' => $ep[3],
                'response_time_ms' => $ep[4] + rand(-30, 80),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PostmanRuntime/7.39.0',
                'request_payload' => ($ep[1] === 'POST') ? ['request' => ['t_sep' => ['noKartu' => '0001234567890', 'tglSep' => date('Y-m-d')]]] : null,
                'response_summary' => ['metaData' => ['code' => (string) $ep[3], 'message' => $ep[3] === 200 ? 'Sukses' : 'Data tidak valid']],
                'is_mock' => false,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        $this->info('Successfully seeded 28 sample gateway logs.');
        return Command::SUCCESS;
    }
}
