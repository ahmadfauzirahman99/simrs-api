<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminVersionPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'superadmin']);
    }

    public function test_guest_is_redirected_from_version_page(): void
    {
        $response = $this->get('/admin/version');
        $response->assertRedirect('/login');
    }

    public function test_superadmin_can_open_version_page(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('superadmin');

        $response = $this->actingAs($admin)->get('/admin/version');

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Version/Index')
                ->has('currentVersion')
                ->has('releases')
            );
    }

    public function test_check_update_endpoint_returns_json(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('superadmin');

        $response = $this->actingAs($admin)->getJson('/admin/version/check');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'status',
                'current_version',
            ]);
    }
}
