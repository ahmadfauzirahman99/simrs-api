<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminClientManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->superadmin = User::where('email', 'admin@simrs.local')->firstOrFail();
    }

    public function test_guest_cannot_access_admin_clients(): void
    {
        $response = $this->get('/admin/clients');
        $response->assertRedirect('/login');
    }

    public function test_client_role_cannot_access_admin_clients(): void
    {
        $client = User::create([
            'name' => 'Klinik Biasa',
            'email' => 'klinik@biasa.id',
            'password' => Hash::make('secret123'),
            'subscription_ends_at' => now()->addDays(30),
        ]);
        $client->assignRole('client');

        $response = $this->actingAs($client)->get('/admin/clients');
        $response->assertForbidden();
    }

    public function test_superadmin_can_access_admin_clients_and_see_metrics_and_list(): void
    {
        $client = User::create([
            'name' => 'RS Harapan Bunda',
            'email' => 'it@harapanbunda.id',
            'phone' => '081234567890',
            'password' => Hash::make('secret123'),
            'subscription_ends_at' => now()->addDays(30),
        ]);
        $client->createToken('prod-token');

        $today = now()->format('Y-m-d');
        Cache::put("client_req_{$client->id}_{$today}", 15, now()->endOfDay());

        $response = $this->actingAs($this->superadmin)->get('/admin/clients');

        $response->assertOk();
    }

    public function test_superadmin_can_filter_clients(): void
    {
        User::create([
            'name' => 'RS Siloam Test',
            'email' => 'siloam@test.id',
            'password' => Hash::make('secret123'),
        ]);

        User::create([
            'name' => 'Klinik Kimia Farma',
            'email' => 'kimia@farma.id',
            'password' => Hash::make('secret123'),
        ]);

        $searchResponse = $this->actingAs($this->superadmin)->get('/admin/clients?search=Siloam');
        $searchResponse->assertOk();

        $statusResponse = $this->actingAs($this->superadmin)->get('/admin/clients?status=trial');
        $statusResponse->assertOk();
    }

    public function test_superadmin_can_extend_subscription_by_30_days(): void
    {
        $client = User::create([
            'name' => 'RS Surya Sehat',
            'email' => 'surya@sehat.id',
            'password' => Hash::make('secret123'),
        ]);

        $this->assertNull($client->subscription_ends_at);

        $response = $this->actingAs($this->superadmin)
            ->post("/admin/clients/{$client->id}/extend", ['days' => 30]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $client->refresh();
        $this->assertNotNull($client->subscription_ends_at);
        $this->assertTrue($client->subscription_ends_at->isFuture());
    }

    public function test_superadmin_can_reset_client_to_trial(): void
    {
        $client = User::create([
            'name' => 'RS Berhenti Langganan',
            'email' => 'stop@langganan.id',
            'password' => Hash::make('secret123'),
            'subscription_ends_at' => now()->addDays(20),
        ]);

        $response = $this->actingAs($this->superadmin)
            ->post("/admin/clients/{$client->id}/set-trial");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $client->refresh();
        $this->assertNull($client->subscription_ends_at);
    }

    public function test_superadmin_can_revoke_client_tokens(): void
    {
        $client = User::create([
            'name' => 'RS Ganti Token',
            'email' => 'token@rs.id',
            'password' => Hash::make('secret123'),
        ]);
        $client->createToken('token-1');
        $client->createToken('token-2');

        $this->assertCount(2, $client->tokens);

        $response = $this->actingAs($this->superadmin)
            ->post("/admin/clients/{$client->id}/revoke-tokens");

        $response->assertRedirect();
        $this->assertCount(0, $client->fresh()->tokens);
    }

    public function test_superadmin_can_delete_client_account(): void
    {
        $client = User::create([
            'name' => 'RS Dihapus',
            'email' => 'hapus@rs.id',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->actingAs($this->superadmin)
            ->delete("/admin/clients/{$client->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $client->id]);
    }

    public function test_superadmin_cannot_delete_own_account(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->delete("/admin/clients/{$this->superadmin->id}");

        $response->assertStatus(400);
        $this->assertDatabaseHas('users', ['id' => $this->superadmin->id]);
    }
}
