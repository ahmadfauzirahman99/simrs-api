<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiClientRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Cache::flush();
    }

    private function makeClient(string $email): string
    {
        $user = User::create([
            'name' => $email,
            'email' => $email,
            'password' => Hash::make('password123'),
        ]);
        $user->assignRole('client');

        return $user->createToken('default-key')->plainTextToken;
    }

    private function callApi(array $headers, string $ip = '10.0.0.1')
    {
        return $this->withHeaders($headers)
            ->withServerVariables(['REMOTE_ADDR' => $ip])
            ->getJson('/api/v1/');
    }

    public function test_x_api_key_clients_behind_same_ip_get_separate_limits(): void
    {
        $tokenA = $this->makeClient('a@rs.id');
        $tokenB = $this->makeClient('b@rs.id');

        $this->callApi(['X-API-KEY' => $tokenA])->assertHeader('X-RateLimit-Remaining', '119');
        // Client B di IP yang sama tidak boleh ikut memakai jatah client A.
        $this->callApi(['X-API-KEY' => $tokenB])->assertHeader('X-RateLimit-Remaining', '119');
    }

    public function test_same_client_shares_limit_across_ips_and_header_styles(): void
    {
        $token = $this->makeClient('a@rs.id');

        $this->callApi(['X-API-KEY' => $token], '10.0.0.1')->assertHeader('X-RateLimit-Remaining', '119');
        $this->callApi(['Authorization' => "Bearer {$token}"], '10.0.0.2')->assertHeader('X-RateLimit-Remaining', '118');
        $this->callApi(['X-API-KEY' => $token], '10.0.0.3')->assertHeader('X-RateLimit-Remaining', '117');
    }

    public function test_regenerated_token_keeps_client_limit_bucket(): void
    {
        $user = User::create(['name' => 'c', 'email' => 'c@rs.id', 'password' => Hash::make('password123')]);
        $user->assignRole('client');

        $this->callApi(['X-API-KEY' => $user->createToken('k1')->plainTextToken])->assertHeader('X-RateLimit-Remaining', '119');
        // Ganti token tidak boleh me-reset jatah per menit.
        $this->callApi(['X-API-KEY' => $user->createToken('k2')->plainTextToken])->assertHeader('X-RateLimit-Remaining', '118');
    }

    public function test_random_invalid_tokens_do_not_get_fresh_buckets(): void
    {
        $this->callApi(['Authorization' => 'Bearer palsu-1'])->assertStatus(401)->assertHeader('X-RateLimit-Remaining', '119');
        // Token acak baru dari IP yang sama tetap memakai jatah IP tersebut.
        $this->callApi(['Authorization' => 'Bearer palsu-2'])->assertStatus(401)->assertHeader('X-RateLimit-Remaining', '118');
    }

    public function test_trial_quota_counts_every_request_and_blocks_after_limit(): void
    {
        $token = $this->makeClient('trial@rs.id');
        $user = User::where('email', 'trial@rs.id')->first();
        $key = "client_req_{$user->id}_" . now()->format('Y-m-d');
        Cache::put($key, 48, now()->endOfDay());

        $this->callApi(['X-API-KEY' => $token])->assertOk()->assertHeader('X-Quota-Remaining', '1');
        $this->callApi(['X-API-KEY' => $token])->assertOk()->assertHeader('X-Quota-Remaining', '0');
        $this->callApi(['X-API-KEY' => $token])->assertStatus(429);

        // Request yang ditolak tidak ikut terhitung sebagai pemakaian.
        $this->assertSame(50, (int) Cache::get($key));
    }
}
