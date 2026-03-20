<?php

namespace Tests\Feature\Api;

use App\Models\SatuSehatToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SatuSehatIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.satusehat.env' => 'DEV',
            'services.satusehat.auth_dev' => 'https://satusehat-auth.test/oauth2/v1',
            'services.satusehat.fhir_dev' => 'https://satusehat-fhir.test/fhir-r4/v1',
            'services.satusehat.client_id_dev' => 'client-id-dev',
            'services.satusehat.client_secret_dev' => 'client-secret-dev',
            'services.satusehat.org_id_dev' => 'ORG-123',
        ]);
    }

    public function test_encounter_send_requests_token_and_submits_payload(): void
    {
        Http::fake([
            'https://satusehat-auth.test/*' => Http::response([
                'access_token' => 'demo-access-token',
                'expires_in' => 3600,
            ], 200),
            'https://satusehat-fhir.test/*' => Http::response([
                'resourceType' => 'Encounter',
                'id' => 'enc-001',
                'status' => 'arrived',
            ], 200),
        ]);

        $payload = [
            'reg_id' => 'REG-001',
            'consultation_method' => 'RAJAL',
            'arrived' => '2026-03-19 08:00:00',
            'inprogress' => '2026-03-19 08:15:00',
            'finished' => '2026-03-19 09:00:00',
            'patient_satu_sehat' => 'PAT-001',
            'patient_name' => 'Budi',
            'doctor_satu_sehat' => 'DOC-001',
            'unit_name' => 'Poli Umum',
            'location_id' => 'LOC-001',
            'location_name' => 'Ruang Poli Umum',
        ];

        $response = $this->postJson('/api/v1/satu-sehat/encounter/send', $payload);

        $response->assertOk()
            ->assertJsonPath('metaData.code', '200')
            ->assertJsonPath('metaData.message', 'Berhasil kirim Encounter ke SATUSEHAT')
            ->assertJsonPath('response.satusehat_response.id', 'enc-001')
            ->assertJsonPath('response.request_payload.resourceType', 'Encounter');

        $this->assertDatabaseHas('satusehat_tokens', [
            'environment' => 'DEV',
            'token' => 'demo-access-token',
        ]);

        Http::assertSentCount(2);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://satusehat-auth.test/oauth2/v1/accesstoken?grant_type=client_credentials'
                && $request->method() === 'POST';
        });

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://satusehat-fhir.test/fhir-r4/v1/Encounter'
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Bearer demo-access-token')
                && ($data['resourceType'] ?? null) === 'Encounter'
                && ($data['subject']['reference'] ?? null) === 'Patient/PAT-001';
        });
    }

    public function test_encounter_send_reuses_cached_token_without_requesting_new_one(): void
    {
        SatuSehatToken::create([
            'environment' => 'DEV',
            'token' => 'cached-token',
            'created_at_token' => now(),
            'expired' => 3600,
        ]);

        Http::fake([
            'https://satusehat-fhir.test/*' => Http::response([
                'resourceType' => 'Encounter',
                'id' => 'enc-002',
                'status' => 'arrived',
            ], 200),
        ]);

        $payload = [
            'reg_id' => 'REG-002',
            'consultation_method' => 'RAJAL',
            'arrived' => '2026-03-19 08:00:00',
            'inprogress' => null,
            'finished' => null,
            'patient_satu_sehat' => 'PAT-002',
            'patient_name' => 'Siti',
            'doctor_satu_sehat' => 'DOC-002',
            'unit_name' => 'Poli Anak',
            'location_id' => 'LOC-002',
            'location_name' => 'Ruang Poli Anak',
        ];

        $response = $this->postJson('/api/v1/satu-sehat/encounter/send', $payload);

        $response->assertOk()
            ->assertJsonPath('response.satusehat_response.id', 'enc-002');

        Http::assertSentCount(1);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://satusehat-fhir.test/fhir-r4/v1/Encounter'
                && $request->hasHeader('Authorization', 'Bearer cached-token');
        });
    }
}
