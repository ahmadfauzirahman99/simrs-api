<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BpjsAutoMockIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_postman_request_with_x_mock_mode_header_returns_mock_response(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Mock-Mode' => 'true',
        ])->getJson('/api/v1/bpjs/peserta?nomor=0001234567890&tipe=1');

        $response->assertOk()
            ->assertJsonPath('metaData.code', '200')
            ->assertJsonStructure([
                'metaData' => ['code', 'message'],
                'response' => ['peserta'],
            ])
            ->assertHeader('X-Gateway-Mode', 'mock-simulation');
    }

    public function test_postman_request_fallback_to_mock_when_bpjs_unconfigured(): void
    {
        // Set konfigurasi BPJS kembali ke dummy placeholder bawaan (.env.example)
        config([
            'services.bpjs_v3.base_url' => 'URL_BPJS_V3',
            'services.bpjs_v3.cons_id' => 'CONS_ID_BPJS_V3',
            'services.bpjs_v3.secret_key' => 'SECRET_KEY_BP',
        ]);

        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Tanpa header X-Mock-Mode pun, sistem otomatis fallback ke mock response daripada melempar cURL error 500
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/v1/bpjs/peserta?nomor=0001234567890&tipe=1');

        $response->assertOk()
            ->assertJsonPath('metaData.code', '200')
            ->assertHeader('X-Gateway-Mode', 'mock-simulation');
    }
}
