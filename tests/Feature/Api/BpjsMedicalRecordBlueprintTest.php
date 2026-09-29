<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class BpjsMedicalRecordBlueprintTest extends TestCase
{
    public function test_medical_record_resource_blueprints_can_be_loaded(): void
    {
        $response = $this->getJson('/api/v1/bpjs/rekam-medis/resources');

        $response->assertOk()
            ->assertJsonPath('metaData.code', '200')
            ->assertJsonPath('response.items.0.resource', 'bundle');
    }

    public function test_medical_record_single_resource_blueprint_can_be_loaded(): void
    {
        $response = $this->getJson('/api/v1/bpjs/rekam-medis/resources/patient');

        $response->assertOk()
            ->assertJsonPath('metaData.code', '200')
            ->assertJsonPath('response.item.resource', 'patient')
            ->assertJsonPath('response.generated_template.resourceType', 'Patient');
    }

    public function test_patient_practitioner_and_organization_blueprints_include_richer_fields(): void
    {
        $patient = $this->getJson('/api/v1/bpjs/rekam-medis/resources/patient');
        $practitioner = $this->getJson('/api/v1/bpjs/rekam-medis/resources/practitioner');
        $organization = $this->getJson('/api/v1/bpjs/rekam-medis/resources/organization');

        $patient->assertOk()
            ->assertSee('birthDate')
            ->assertSee('gender')
            ->assertSee('address')
            ->assertSee('identifier');

        $practitioner->assertOk()
            ->assertSee('birthDate')
            ->assertSee('gender')
            ->assertSee('telecom')
            ->assertSee('address');

        $organization->assertOk()
            ->assertSee('identifier')
            ->assertSee('alias')
            ->assertSee('contact')
            ->assertSee('address');
    }
}
