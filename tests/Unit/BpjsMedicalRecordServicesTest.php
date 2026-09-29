<?php

namespace Tests\Unit;

use App\Services\Bpjs\MedicalRecord\BuildBpjsMedicalRecordRequestService;
use App\Services\Bpjs\MedicalRecord\EncodeBpjsMedicalRecordPayloadService;
use App\Services\Bpjs\MedicalRecord\GenerateBpjsMedicalRecordBundleService;
use Tests\TestCase;

class BpjsMedicalRecordServicesTest extends TestCase
{
    public function test_it_loads_sample_bundle_and_creates_snapshot(): void
    {
        config()->set('services.bpjs_medical_record.sample_path', base_path('sampling.normalized.json'));

        $service = new GenerateBpjsMedicalRecordBundleService();
        $bundle = $service->fromSample();
        $snapshot = $service->makeSnapshot($bundle);

        $this->assertSame('Bundle', $bundle['resourceType']);
        $this->assertSame('Document', $bundle['type']);
        $this->assertGreaterThan(0, count($bundle['entry']));
        $this->assertSame('Bundle', $snapshot['resource_type']);
        $this->assertArrayHasKey('Patient', $snapshot['resource_summary']);
    }

    public function test_it_encodes_bundle_into_gzip_and_encrypted_payload(): void
    {
        $bundle = [
            'resourceType' => 'Bundle',
            'type' => 'Document',
            'entry' => [
                [
                    'resource' => [
                        'resourceType' => 'Patient',
                        'id' => 'PAT-001',
                    ],
                ],
            ],
        ];

        $service = new EncodeBpjsMedicalRecordPayloadService();
        $encoded = $service->encode($bundle, 'demo-cons-id', 'demo-secret', '0082R012');

        $this->assertArrayHasKey('gzip_payload', $encoded);
        $this->assertArrayHasKey('encrypted_payload', $encoded);
        $this->assertNotEmpty($encoded['gzip_payload']);
        $this->assertNotEmpty($encoded['encrypted_payload']);
        $this->assertNotSame($encoded['gzip_payload'], $encoded['encrypted_payload']);
    }

    public function test_it_builds_bpjs_medical_record_request(): void
    {
        $service = new BuildBpjsMedicalRecordRequestService();
        $payload = $service->build(
            '0082R0120925V000002',
            '1',
            '09',
            '2025',
            'encrypted-demo-payload'
        );

        $this->assertSame('0082R0120925V000002', $payload['request']['noSep']);
        $this->assertSame('1', $payload['request']['jnsPelayanan']);
        $this->assertSame('09', $payload['request']['bulan']);
        $this->assertSame('2025', $payload['request']['tahun']);
        $this->assertSame('encrypted-demo-payload', $payload['request']['dataMR']);
    }
}
