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

        $login->assertRedirect(route('admin.bpjs-tester'));

        $response = $this->actingAs($user)->get('/admin/bpjs-tester');

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
}
