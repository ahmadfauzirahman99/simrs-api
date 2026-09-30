<?php

namespace Tests\Feature\Web;

use App\Models\PaymentProof;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CyberSecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'superadmin']);
        Role::firstOrCreate(['name' => 'client']);

        $this->superadmin = User::factory()->create([
            'email' => 'admin@simrs.local',
        ]);
        $this->superadmin->assignRole('superadmin');

        $this->client = User::factory()->create([
            'email' => 'rs.sehat@simrs.local',
        ]);
        $this->client->assignRole('client');
    }

    public function test_defense_in_depth_blocks_client_from_admin_management_routes(): void
    {
        // Client trying to access superadmin routes must strictly receive 403
        $this->actingAs($this->client)
            ->get(route('admin.dashboard'))
            ->assertStatus(403);

        $this->actingAs($this->client)
            ->get(route('admin.clients'))
            ->assertStatus(403);

        $this->actingAs($this->client)
            ->get(route('admin.pricing'))
            ->assertStatus(403);

        $this->actingAs($this->client)
            ->post(route('admin.clients.extend', $this->client))
            ->assertStatus(403);
    }

    public function test_superadmin_cannot_be_deleted_by_other_admin(): void
    {
        $otherSuperadmin = User::factory()->create(['email' => 'other.admin@simrs.local']);
        $otherSuperadmin->assignRole('superadmin');

        $response = $this->actingAs($this->superadmin)
            ->delete(route('admin.clients.destroy', $otherSuperadmin));

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $otherSuperadmin->id]);
    }

    public function test_payment_proof_cannot_be_double_approved_race_condition(): void
    {
        $proof = PaymentProof::create([
            'user_id' => $this->client->id,
            'plan_name' => 'Monthly SaaS',
            'amount' => 450000,
            'proof_image' => 'payment_proofs/sample.jpg',
            'original_filename' => 'sample.jpg',
            'mime_type' => 'image/jpeg',
            'file_size_bytes' => 1024,
            'status' => 'approved',
            'verified_at' => now(),
            'verified_by' => $this->superadmin->id,
        ]);

        $response = $this->actingAs($this->superadmin)
            ->post(route('admin.payment-proofs.approve', $proof), ['days' => 30]);

        $response->assertStatus(422);
    }

    public function test_security_headers_are_strictly_present_on_web_and_api(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->assertHeader('X-Permitted-Cross-Domain-Policies', 'none');
    }

    public function test_public_upload_directory_has_script_execution_prevention_htaccess(): void
    {
        $htaccessPath = storage_path('app/public/.htaccess');
        $this->assertFileExists($htaccessPath);

        $content = file_get_contents($htaccessPath);
        $this->assertStringContainsString('Deny from all', $content);
        $this->assertStringContainsString('php', $content);
        $this->assertStringContainsString('Options -Indexes -ExecCGI', $content);
    }
}
