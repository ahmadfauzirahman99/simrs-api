<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminBpjsTesterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_is_redirected_to_login_page(): void
    {
        $response = $this->get('/admin/bpjs-tester');

        $response->assertRedirect('/login');
    }

    public function test_superadmin_can_login_and_open_bpjs_tester(): void
    {
        $user = User::where('email', 'admin@simrs.local')->firstOrFail();

        $login = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $login->assertRedirect(route('admin.clients'));

        $response = $this->actingAs($user)->get('/admin/bpjs-tester');

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    public function test_superadmin_can_open_bpjs_medical_record_console(): void
    {
        $user = User::where('email', 'admin@simrs.local')->firstOrFail();

        $response = $this->actingAs($user)->get('/admin/bpjs-medical-record');

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    public function test_non_superadmin_cannot_login_to_admin_console(): void
    {
        $user = User::create([
            'name' => 'Operator BPJS',
            'email' => 'operator@simrs.local',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('bpjs');

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_user_with_active_monthly_subscription_can_login_and_access_bpjs_tester(): void
    {
        $user = User::create([
            'name' => 'Dokter Pelanggan',
            'email' => 'dokter@faskes.id',
            'password' => Hash::make('secret123'),
            'subscription_ends_at' => now()->addDays(30),
        ]);

        $login = $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $login->assertRedirect(route('admin.bpjs-tester'));
        $this->assertAuthenticatedAs($user);

        $response = $this->actingAs($user)->get('/admin/bpjs-tester');
        $response->assertOk();
    }

    public function test_user_with_expired_subscription_cannot_login(): void
    {
        $user = User::create([
            'name' => 'Dokter Expired',
            'email' => 'expired@faskes.id',
            'password' => Hash::make('secret123'),
            'subscription_ends_at' => now()->subDay(),
        ]);

        $login = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $login->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_user_with_expired_subscription_cannot_access_protected_route(): void
    {
        $user = User::create([
            'name' => 'Dokter Expired',
            'email' => 'expired-session@faskes.id',
            'password' => Hash::make('secret123'),
            'subscription_ends_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->get('/admin/bpjs-tester');
        $response->assertRedirect(route('login'));
    }

    public function test_subscription_console_commands(): void
    {
        $this->artisan('subscription:create', [
            'email' => 'subscriber@rs.id',
            '--name' => 'RS Cemerlang',
            '--months' => 1,
        ])->assertSuccessful();

        $user = User::where('email', 'subscriber@rs.id')->firstOrFail();
        $this->assertTrue($user->hasActiveSubscription());

        $this->artisan('subscription:extend', [
            'email' => 'subscriber@rs.id',
            '--months' => 2,
        ])->assertSuccessful();

        $user->refresh();
        $this->assertTrue($user->subscription_ends_at->gt(now()->addMonths(2)));
    }

    public function test_guest_can_login_via_demo_login_and_access_bpjs_tester(): void
    {
        $response = $this->post('/demo-login');

        $response->assertRedirect(route('admin.bpjs-tester'));
        $this->assertAuthenticated();
        $this->assertTrue(auth()->user()->hasRole('demo'));

        $testerResponse = $this->get('/admin/bpjs-tester');
        $testerResponse->assertOk();
    }

    public function test_user_can_encrypt_and_decrypt_bpjs_payload_preview(): void
    {
        $user = User::where('email', 'admin@simrs.local')->firstOrFail();

        $originalData = [
            'peserta' => [
                'nama' => 'Budi Santoso',
                'noKartu' => '0001234567890',
            ],
        ];

        // 1. Encrypt Preview
        $encryptResponse = $this->actingAs($user)->postJson('/admin/bpjs-tester/encrypt-preview', [
            'payload' => [
                'metaData' => ['code' => '200', 'message' => 'OK'],
                'response' => $originalData,
            ],
            'cons_id' => '12345',
            'secret_key' => 'secret123',
            'timestamp' => '1727602280',
        ]);

        $encryptResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'raw_bpjs_response' => ['metaData', 'response'],
                'crypto_details' => [
                    'algorithm',
                    'compression',
                    'cons_id',
                    'timestamp',
                    'derived_key',
                    'signature_hmac_sha256',
                ],
            ]);

        $ciphertext = $encryptResponse->json('raw_bpjs_response.response');
        $this->assertNotEmpty($ciphertext);
        $this->assertIsString($ciphertext);

        // 2. Decrypt Preview
        $decryptResponse = $this->actingAs($user)->postJson('/admin/bpjs-tester/decrypt-preview', [
            'ciphertext' => $ciphertext,
            'cons_id' => '12345',
            'secret_key' => 'secret123',
            'timestamp' => '1727602280',
        ]);

        $decryptResponse->assertOk()
            ->assertJsonPath('success', true);

        $decryptedData = json_decode(json_encode($decryptResponse->json('decrypted')), true);
        $this->assertEquals($originalData, $decryptedData);
    }

    public function test_demo_user_can_access_encrypt_preview(): void
    {
        $this->post('/demo-login');
        $this->assertAuthenticated();

        $response = $this->postJson('/admin/bpjs-tester/encrypt-preview', [
            'payload' => ['response' => ['status' => 'OK']],
        ]);

        $response->assertOk()->assertJsonPath('success', true);
    }
}

