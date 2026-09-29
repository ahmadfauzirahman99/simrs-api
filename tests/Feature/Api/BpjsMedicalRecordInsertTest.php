<?php

namespace Tests\Feature\Api;

use App\Models\BpjsMedicalRecordJob;
use App\Models\BpjsMedicalRecordPayload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BpjsMedicalRecordInsertTest extends TestCase
{
    use RefreshDatabase;

    public function test_medical_record_insert_endpoint_saves_snapshot_and_sends_request(): void
    {
        config()->set('services.bpjs_medical_record.sample_path', base_path('sampling.normalized.json'));
        config()->set('services.bpjs_medical_record.provider_code', '0082R012');
        config()->set('services.bpjs_medical_record.base_url', 'https://bpjs-medical.test');
        config()->set('services.bpjs_v3.cons_id', 'demo-cons-id');
        config()->set('services.bpjs_v3.secret_key', 'demo-secret');
        config()->set('services.bpjs_v3.user_key', 'demo-user-key');

        Http::fake([
            'https://bpjs-medical.test/*' => Http::response([
                'metaData' => [
                    'code' => '200',
                    'message' => 'Sukses',
                ],
                'response' => null,
            ], 200),
        ]);

        $response = $this->postJson('/api/v1/bpjs/rekam-medis/insert', [
            'no_sep' => '0082R0120925V000002',
            'jns_pelayanan' => '1',
            'bulan' => '09',
            'tahun' => '2025',
        ]);

        $response->assertOk()
            ->assertJsonPath('metaData.code', '200')
            ->assertJsonPath('metaData.message', 'Insert medical record BPJS berhasil dikirim');

        $this->assertDatabaseCount('bpjs_medical_record_jobs', 1);
        $this->assertDatabaseCount('bpjs_medical_record_payloads', 1);

        $job = BpjsMedicalRecordJob::first();
        $payload = BpjsMedicalRecordPayload::first();

        $this->assertSame('sent', $job->status);
        $this->assertNotNull($job->sent_at);
        $this->assertNotEmpty($payload->encrypted_payload);
        $this->assertNotEmpty($payload->request_json);
        $this->assertSame('200', $payload->bpjs_response_code);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://bpjs-medical.test/eclaim/rekammedis/insert'
                && $request->method() === 'POST'
                && str_contains($request->body(), '"noSep":"0082R0120925V000002"')
                && str_contains($request->body(), '"dataMR":"');
        });
    }
}
