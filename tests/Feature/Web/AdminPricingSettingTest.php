<?php

namespace Tests\Feature\Web;

use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPricingSettingTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->superadmin = User::where('email', 'admin@simrs.local')->firstOrFail();
    }

    public function test_guest_cannot_access_admin_pricing(): void
    {
        $response = $this->get('/admin/pricing');
        $response->assertRedirect('/login');
    }

    public function test_client_role_cannot_access_admin_pricing(): void
    {
        $client = User::create([
            'name' => 'Klinik Biasa',
            'email' => 'klinik@biasa.id',
            'password' => Hash::make('secret123'),
            'subscription_ends_at' => now()->addDays(30),
        ]);
        $client->assignRole('client');

        $response = $this->actingAs($client)->get('/admin/pricing');
        $response->assertForbidden();
    }

    public function test_superadmin_can_access_admin_pricing_page(): void
    {
        $response = $this->actingAs($this->superadmin)->get('/admin/pricing');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Settings/Pricing')
            ->has('settings')
            ->where('settings.pricing_monthly_price', 450000)
            ->where('settings.pricing_monthly_promo_code', 'SIMRS450')
        );
    }

    public function test_superadmin_can_update_pricing_and_promo_settings(): void
    {
        $payload = [
            'pricing_monthly_title' => 'Lisensi Bulanan Spesial',
            'pricing_monthly_tag' => 'Best Value',
            'pricing_monthly_desc' => 'Akses penuh tanpa limit harian.',
            'pricing_monthly_price' => 450000,
            'pricing_monthly_strike_price' => 750000,
            'pricing_monthly_promo_active' => true,
            'pricing_monthly_promo_badge' => 'Diskon 40% Spesial',
            'pricing_monthly_promo_code' => 'PROMOSIMRS',
            'pricing_monthly_period' => '/ bulan',
            'pricing_monthly_features' => ['Fitur A', 'Fitur B'],
            'pricing_monthly_cta_text' => 'Langganan Sekarang',

            'pricing_trial_title' => 'Coba Gratis',
            'pricing_trial_tag' => 'Trial 100',
            'pricing_trial_desc' => 'Uji coba sistem.',
            'pricing_trial_price' => 0,
            'pricing_trial_limit' => 100,
            'pricing_trial_features' => ['Limit 100 Req'],
            'pricing_trial_cta_text' => 'Mulai Gratis',

            'pricing_hospital_title' => 'Source Code Hospital',
            'pricing_hospital_tag' => 'On-Premise',
            'pricing_hospital_desc' => 'Source code lengkap.',
            'pricing_hospital_price' => 4500000,
            'pricing_hospital_strike_price' => 6500000,
            'pricing_hospital_promo_active' => true,
            'pricing_hospital_promo_badge' => 'Hemat Rp 2 Jt',
            'pricing_hospital_period' => '(Sekali Bayar)',
            'pricing_hospital_features' => ['Full Code'],
            'pricing_hospital_cta_text' => 'Beli Source Code',

            'promo_banner_active' => true,
            'promo_banner_text' => 'Pengumuman promo baru aktif!',
            'promo_banner_link' => '#pricing',

            'whatsapp_contact' => '628999888777',
            'support_email' => 'promo@simrs.id',

            'payment_bank_name' => 'Bank BCA',
            'payment_account_number' => '8831294011',
            'payment_account_holder' => 'Ahmad Fauzi Rahman',
            'payment_bank_name_2' => 'Bank Mandiri',
            'payment_account_number_2' => '1234567890',
            'payment_account_holder_2' => 'Ahmad Fauzi Rahman',
            'payment_qris_active' => true,
            'payment_qris_image' => '/images/qris.jpeg',
        ];

        $response = $this->actingAs($this->superadmin)->post('/admin/pricing', $payload);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify values stored in DB and cached properly
        $this->assertEquals(450000, SiteSetting::get('pricing_monthly_price'));
        $this->assertEquals(750000, SiteSetting::get('pricing_monthly_strike_price'));
        $this->assertTrue(SiteSetting::get('pricing_monthly_promo_active'));
        $this->assertEquals('PROMOSIMRS', SiteSetting::get('pricing_monthly_promo_code'));
        $this->assertEquals('Diskon 40% Spesial', SiteSetting::get('pricing_monthly_promo_badge'));
        $this->assertEquals(100, SiteSetting::get('pricing_trial_limit'));
        $this->assertEquals('628999888777', SiteSetting::get('whatsapp_contact'));
        $this->assertEquals('8831294011', SiteSetting::get('payment_account_number'));
        $this->assertEquals('Bank Mandiri', SiteSetting::get('payment_bank_name_2'));
        $this->assertEquals('/images/qris.jpeg', SiteSetting::get('payment_qris_image'));
    }

    public function test_ensure_client_api_key_middleware_enforces_dynamic_quota_limit(): void
    {
        SiteSetting::set('pricing_trial_limit', 5);
        SiteSetting::set('pricing_monthly_price', 450000);
        SiteSetting::set('pricing_monthly_promo_code', 'SIMRS450');

        $client = User::create([
            'name' => 'Klinik Uji Coba',
            'email' => 'ujicoba@klinik.id',
            'password' => Hash::make('password123'),
        ]);
        $client->assignRole('client');
        $token = $client->createToken('test-token')->plainTextToken;

        $today = now()->format('Y-m-d');
        Cache::put("client_req_{$client->id}_{$today}", 5, now()->endOfDay());

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/bpjs/peserta?nik=3201010101010001');

        $response->assertStatus(429);
        $response->assertJsonPath('metaData.code', '429');
        $this->assertStringContainsString('450.000', $response->json('metaData.message'));
        $this->assertStringContainsString('SIMRS450', $response->json('metaData.message'));
        $this->assertStringContainsString('5 request/hari', $response->json('metaData.message'));
    }

    public function test_login_and_register_pages_reflect_dynamic_trial_limit(): void
    {
        SiteSetting::set('pricing_trial_limit', 100);

        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Auth/Login')
                ->where('trialLimit', 100)
            );

        $this->get('/register')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Auth/Register')
                ->where('trialLimit', 100)
            );
    }
}
