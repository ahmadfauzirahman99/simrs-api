<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BpjsMedicalRecordPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_medical_record_preview_endpoint_builds_request_payload(): void
    {
        config()->set('services.bpjs_medical_record.sample_path', base_path('sampling.normalized.json'));
        config()->set('services.bpjs_medical_record.provider_code', '0082R012');
        config()->set('services.bpjs_v3.cons_id', 'demo-cons-id');
        config()->set('services.bpjs_v3.secret_key', 'demo-secret');

        $response = $this->postJson('/api/v1/bpjs/rekam-medis/preview', [
            'no_sep' => '0082R0120925V000002',
            'jns_pelayanan' => '1',
            'bulan' => '09',
            'tahun' => '2025',
        ]);

        $response->assertOk()
            ->assertJsonPath('metaData.code', '200')
            ->assertJsonPath('metaData.message', 'Preview medical record BPJS berhasil dibuat')
            ->assertJsonPath('response.request_payload.request.noSep', '0082R0120925V000002')
            ->assertJsonPath('response.request_payload.request.jnsPelayanan', '1')
            ->assertJsonPath('response.request_payload.request.bulan', '09')
            ->assertJsonPath('response.request_payload.request.tahun', '2025');

        $this->assertNotEmpty($response->json('response.request_payload.request.dataMR'));
    }

    public function test_medical_record_preview_endpoint_requires_required_fields(): void
    {
        $response = $this->postJson('/api/v1/bpjs/rekam-medis/preview', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['no_sep', 'jns_pelayanan', 'bulan', 'tahun']);
    }

    public function test_medical_record_preview_endpoint_can_use_bundle_from_request(): void
    {
        config()->set('services.bpjs_medical_record.provider_code', '0082R012');
        config()->set('services.bpjs_v3.cons_id', 'demo-cons-id');
        config()->set('services.bpjs_v3.secret_key', 'demo-secret');

        $response = $this->postJson('/api/v1/bpjs/rekam-medis/preview', [
            'no_sep' => '0082R0120925V000002',
            'jns_pelayanan' => '1',
            'bulan' => '09',
            'tahun' => '2025',
            'bundle' => [
                'resourceType' => 'Bundle',
                'id' => 'bundle-from-ui',
                'type' => 'Document',
                'identifier' => [
                    'system' => 'https://rs.example.id/bundle',
                    'value' => 'UI-001',
                ],
                'entry' => [
                    [
                        'resource' => [
                            'resourceType' => 'Patient',
                            'id' => 'patient-ui-001',
                            'name' => [
                                ['text' => 'Patient Dari UI'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('response.bundle_snapshot.bundle_id', 'bundle-from-ui')
            ->assertJsonPath('response.bundle_snapshot.entry_count', 1)
            ->assertJsonPath('response.source.bundle_source', 'staging_bundle');
    }
}
