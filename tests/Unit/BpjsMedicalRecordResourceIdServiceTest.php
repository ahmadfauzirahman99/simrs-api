<?php

namespace Tests\Unit;

use App\Services\Bpjs\MedicalRecord\GenerateBpjsMedicalRecordResourceIdService;
use Tests\TestCase;

class BpjsMedicalRecordResourceIdServiceTest extends TestCase
{
    public function test_it_generates_resource_id_from_rs_kemenkes_and_service_type(): void
    {
        config()->set('services.bpjs_medical_record.rs_code', '1471079');
        config()->set('services.bpjs_medical_record.kemenkes_code', '3171101');

        $service = app(GenerateBpjsMedicalRecordResourceIdService::class);

        $id = $service->generate(
            jnsPelayanan: '1',
            uuid: '550e8400-e29b-41d4-a716-446655440000',
        );

        $this->assertSame(
            '147107931711011550e8400-e29b-41d4-a716-446655440000',
            $id
        );
    }
}
