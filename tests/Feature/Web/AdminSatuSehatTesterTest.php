<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSatuSehatTesterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guest_is_redirected_from_satusehat_tester(): void
    {
        $response = $this->get('/admin/satusehat-tester');

        $response->assertRedirect('/login');
    }

    public function test_superadmin_can_open_satusehat_tester(): void
    {
        $user = User::where('email', 'admin@simrs.local')->firstOrFail();

        $response = $this->actingAs($user)->get('/admin/satusehat-tester');

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    }
}
