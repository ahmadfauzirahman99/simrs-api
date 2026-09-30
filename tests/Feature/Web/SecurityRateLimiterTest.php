<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SecurityRateLimiterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        RateLimiter::clear('login');
        RateLimiter::clear('register');
        RateLimiter::clear('demo');
        RateLimiter::clear('api-key-regenerate');
    }

    public function test_login_route_is_throttled_after_too_many_attempts(): void
    {
        // 5 allowed attempts
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/login', [
                'email' => 'hacker@test.com',
                'password' => 'wrongpass',
            ]);
            $this->assertNotEquals(429, $response->status());
        }

        // 6th attempt should be throttled (429)
        $throttled = $this->post('/login', [
            'email' => 'hacker@test.com',
            'password' => 'wrongpass',
        ]);

        $this->assertEquals(429, $throttled->status());
    }

    public function test_register_route_is_throttled_after_too_many_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/register', [
                'name' => "RS Test {$i}",
                'email' => "rstest{$i}@mail.com",
                'phone' => '081234567890',
                'password' => 'password123',
            ]);
            $this->assertNotEquals(429, $response->status());
        }

        // 6th attempt throttled
        $throttled = $this->post('/register', [
            'name' => 'RS Spam Bot',
            'email' => 'spambot@mail.com',
            'password' => 'password123',
        ]);

        $this->assertEquals(429, $throttled->status());
    }

    public function test_demo_login_is_throttled(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/demo-login');
            $this->assertNotEquals(429, $response->status());
        }

        $throttled = $this->post('/demo-login');
        $this->assertEquals(429, $throttled->status());
    }

    public function test_api_v1_has_rate_limit_headers(): void
    {
        $user = User::create([
            'name' => 'RS Test Rate Limit',
            'email' => 'limit@rs.id',
            'password' => Hash::make('password123'),
        ]);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1');

        $response->assertOk()
            ->assertHeader('X-RateLimit-Limit', '120');
    }
}
