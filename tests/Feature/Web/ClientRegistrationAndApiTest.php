<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Api\Concerns\InteractsWithBpjsTestConfig;
use Tests\TestCase;

class ClientRegistrationAndApiTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithBpjsTestConfig;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->configureBpjsService();
    }

    public function test_guest_can_register_as_client_and_receives_api_key(): void
    {
        $response = $this->post('/register', [
            'name' => 'RSUD Bhakti Medika',
            'email' => 'it@rsudbhakti.id',
            'phone' => '081298765432',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('client.dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'it@rsudbhakti.id',
            'name' => 'RSUD Bhakti Medika',
            'phone' => '081298765432',
        ]);

        $user = User::where('email', 'it@rsudbhakti.id')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertCount(1, $user->tokens);
    }

    public function test_client_can_view_dashboard_and_credentials(): void
    {
        $user = User::create([
            'name' => 'Klinik Mitra Sehat',
            'email' => 'klinik@mitrasehat.id',
            'phone' => '081234567890',
            'password' => Hash::make('password123'),
        ]);
        $user->createToken('default-key');

        $response = $this->actingAs($user)->get('/client/dashboard');

        $response->assertOk();
    }

    public function test_trial_client_can_access_bpjs_tester_studio_ui(): void
    {
        $client = User::create([
            'name' => 'RS Cobak Uji Coba',
            'email' => 'cobak@rs-uji.id',
            'password' => Hash::make('password123'),
        ]);
        $client->assignRole('client');

        $response = $this->actingAs($client)->get('/admin/bpjs-tester');

        $response->assertOk();
    }

    public function test_client_can_login_via_web_login_and_redirects_to_client_dashboard(): void
    {
        $client = User::create([
            'name' => 'RS Klien Login',
            'email' => 'klien@rs-login.id',
            'password' => Hash::make('password123'),
        ]);
        $client->assignRole('client');

        $response = $this->post('/login', [
            'email' => 'klien@rs-login.id',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('client.dashboard'));
        $this->assertAuthenticatedAs($client);
    }

    public function test_client_can_regenerate_api_token(): void
    {
        $user = User::create([
            'name' => 'PT Solusi SIMRS',
            'email' => 'vendor@simrs.id',
            'password' => Hash::make('password123'),
        ]);
        $firstToken = $user->createToken('default-key')->plainTextToken;

        $response = $this->actingAs($user)->post('/client/api-keys/regenerate');

        $response->assertRedirect(route('client.dashboard'));
        $response->assertSessionHas('new_api_key');
        $this->assertCount(1, $user->fresh()->tokens);
    }

    public function test_external_api_request_with_valid_bearer_token(): void
    {
        // Mock BPJS service response
        Http::fake([
            'https://bpjs.test/*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'Sukses'],
                'response' => '',
            ], 200),
        ]);

        $user = User::create([
            'name' => 'RS Demo Integrasi',
            'email' => 'rs@demo-integrasi.id',
            'password' => Hash::make('password123'),
        ]);
        $plainToken = $user->createToken('test-token')->plainTextToken;

        $response = $this->withToken($plainToken)
            ->getJson('/api/v1/bpjs/peserta?nomor=0001234567890&tipe=1');

        $response->assertOk()
            ->assertHeader('X-Client-Id', "CLIENT-{$user->id}")
            ->assertHeader('X-Quota-Type', 'trial-50/day');
    }

    public function test_external_api_request_with_invalid_token_returns_401(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token-sample')
            ->getJson('/api/v1/bpjs/peserta?nomor=0001234567890&tipe=1');

        $response->assertStatus(401)
            ->assertJsonPath('metaData.code', '401');
    }

    public function test_trial_client_exceeding_50_requests_returns_429(): void
    {
        $user = User::create([
            'name' => 'RS Trial Kuota',
            'email' => 'trial@rs-kuota.id',
            'password' => Hash::make('password123'),
        ]);
        $plainToken = $user->createToken('trial-token')->plainTextToken;

        // Simulate 50 requests already used today
        $today = now()->format('Y-m-d');
        Cache::put("client_req_{$user->id}_{$today}", 50, now()->endOfDay());

        $response = $this->withHeader('Authorization', "Bearer {$plainToken}")
            ->getJson('/api/v1/bpjs/peserta?nomor=0001234567890&tipe=1');

        $response->assertStatus(429)
            ->assertJsonPath('metaData.code', '429');
    }

    public function test_pro_client_with_active_subscription_bypasses_50_limit(): void
    {
        Http::fake([
            'https://bpjs.test/*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'Sukses'],
                'response' => '',
            ], 200),
        ]);

        $user = User::create([
            'name' => 'RS Pro Langganan',
            'email' => 'pro@rs-langganan.id',
            'password' => Hash::make('password123'),
            'subscription_ends_at' => now()->addDays(30), // Active monthly subscriber
        ]);
        $plainToken = $user->createToken('pro-token')->plainTextToken;

        // Simulate 75 requests already used today
        $today = now()->format('Y-m-d');
        Cache::put("client_req_{$user->id}_{$today}", 75, now()->endOfDay());

        $response = $this->withHeader('Authorization', "Bearer {$plainToken}")
            ->getJson('/api/v1/bpjs/peserta?nomor=0001234567890&tipe=1');

        $response->assertOk()
            ->assertHeader('X-Quota-Type', 'unlimited');
    }
}
