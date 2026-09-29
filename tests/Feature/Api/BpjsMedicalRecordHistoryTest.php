<?php

namespace Tests\Feature\Api;

use App\Models\BpjsMedicalRecordJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BpjsMedicalRecordHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_medical_record_history_endpoint_returns_latest_jobs(): void
    {
        $job = BpjsMedicalRecordJob::create([
            'encounter_id' => null,
            'patient_id' => null,
            'sep_no' => '0082R0120925V000002',
            'jns_pelayanan' => '1',
            'bulan' => '09',
            'tahun' => '2025',
            'status' => 'sent',
            'last_error' => null,
            'sent_at' => now(),
        ]);

        $job->payloads()->create([
            'bundle_json' => json_encode([
                'resourceType' => 'Bundle',
                'type' => 'Document',
                'entry' => [
                    ['resource' => ['resourceType' => 'Patient', 'id' => 'patient-1']],
                    ['resource' => ['resourceType' => 'Encounter', 'id' => 'encounter-1']],
                ],
            ], JSON_UNESCAPED_SLASHES),
            'normalized_bundle_json' => json_encode([
                'resourceType' => 'Bundle',
                'type' => 'Document',
                'entry' => [
                    ['resource' => ['resourceType' => 'Patient', 'id' => 'patient-1']],
                    ['resource' => ['resourceType' => 'Encounter', 'id' => 'encounter-1']],
                ],
            ], JSON_UNESCAPED_SLASHES),
            'gzip_payload' => 'gzip-demo',
            'encrypted_payload' => 'encrypted-demo',
            'request_json' => json_encode(['request' => ['noSep' => '0082R0120925V000002']], JSON_UNESCAPED_SLASHES),
            'response_json' => json_encode(['metaData' => ['code' => '200', 'message' => 'Sukses']], JSON_UNESCAPED_SLASHES),
            'bpjs_response_code' => '200',
            'bpjs_response_message' => 'Sukses',
        ]);

        $response = $this->getJson('/api/v1/bpjs/rekam-medis/history');

        $response->assertOk()
            ->assertJsonPath('metaData.code', '200')
            ->assertJsonPath('response.items.0.sep_no', '0082R0120925V000002')
            ->assertJsonPath('response.items.0.status', 'sent')
            ->assertJsonPath('response.items.0.bundle_snapshot.entry_count', 2)
            ->assertJsonPath('response.items.0.payload.bpjs_response_code', '200');
    }
}
