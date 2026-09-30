<?php

namespace Tests\Feature\Web;

use App\Models\GatewayLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Feature\Api\Concerns\InteractsWithBpjsTestConfig;
use Tests\TestCase;

class AdminDashboardAndTelemetryTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithBpjsTestConfig;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureBpjsService();
        Role::create(['name' => 'superadmin']);
        Role::create(['name' => 'client']);
    }

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_client_cannot_access_admin_dashboard(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        $response = $this->actingAs($client)->get('/admin/dashboard');
        $response->assertStatus(403);
    }

    public function test_superadmin_can_access_admin_dashboard_and_see_metrics(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('superadmin');

        $client = User::factory()->create(['name' => 'RSUD Uji Coba']);
        $client->assignRole('client');

        GatewayLog::create([
            'user_id' => $client->id,
            'client_name' => $client->name,
            'client_email' => $client->email,
            'endpoint' => '/api/v1/bpjs/peserta',
            'method' => 'GET',
            'service' => 'BPJS VClaim',
            'status_code' => 200,
            'response_time_ms' => 125,
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard/Index')
            ->has('metrics')
            ->has('topEndpoints')
            ->has('hourlyTrend')
            ->has('recentLogs')
            ->has('clients')
            ->where('metrics.total_requests_today', 1)
        );
    }

    public function test_api_calls_are_recorded_in_gateway_logs(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            'https://bpjs.test/*' => \Illuminate\Support\Facades\Http::response([
                'metaData' => ['code' => '200', 'message' => 'Sukses'],
                'response' => '',
            ], 200),
        ]);

        $client = User::create([
            'name' => 'Klinik Medika',
            'email' => 'medika@klinik.id',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
        ]);
        $client->assignRole('client');
        $token = $client->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)
            ->getJson('/api/v1/bpjs/peserta?nomor=0001234567890&tipe=1');

        $response->assertStatus(200);

        $this->assertDatabaseHas('gateway_logs', [
            'user_id' => $client->id,
            'endpoint' => '/api/v1/bpjs/peserta',
            'method' => 'GET',
            'status_code' => 200,
        ]);
    }

    public function test_simulation_telemetry_can_be_logged(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('superadmin');

        $response = $this->actingAs($admin)->postJson('/admin/bpjs-tester/log-simulation', [
            'endpoint' => '/api/v1/bpjs/sep/insert',
            'method' => 'POST',
            'status_code' => 200,
            'response_time_ms' => 310,
            'service' => 'BPJS VClaim',
            'request_payload' => ['request' => ['t_sep' => ['noKartu' => '0001234567890']]],
            'response_summary' => ['metaData' => ['code' => '200', 'message' => 'Sukses']],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('gateway_logs', [
            'endpoint' => '/api/v1/bpjs/sep/insert',
            'method' => 'POST',
            'is_mock' => true,
        ]);
    }

    public function test_postman_collection_is_downloadable_and_valid_json(): void
    {
        $response = $this->get('/downloads/postman-collection');
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/json');

        $json = json_decode($response->getContent(), true);
        $this->assertIsArray($json);
        $this->assertArrayHasKey('info', $json);
        $this->assertEquals('SIMRS Gateway BPJS & SATUSEHAT v3.0', $json['info']['name']);
        $this->assertArrayHasKey('item', $json);
        $this->assertNotEmpty($json['item']);
    }
}
