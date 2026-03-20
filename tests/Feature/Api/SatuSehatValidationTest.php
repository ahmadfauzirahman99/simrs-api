<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class SatuSehatValidationTest extends TestCase
{
    public function test_send_encounter_requires_required_fields(): void
    {
        $response = $this->postJson('/api/v1/satu-sehat/encounter/send', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'reg_id',
                'consultation_method',
                'arrived',
                'patient_satu_sehat',
                'patient_name',
                'doctor_satu_sehat',
                'unit_name',
                'location_id',
                'location_name',
            ]);
    }
}
