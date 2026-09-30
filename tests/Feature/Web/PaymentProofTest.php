<?php

namespace Tests\Feature\Web;

use App\Models\PaymentProof;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentProofTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;
    protected User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->superadmin = User::where('email', 'admin@simrs.local')->firstOrFail();

        $this->client = User::create([
            'name' => 'RSUD Bhakti Medika',
            'email' => 'finance@rsbhakti.id',
            'phone' => '081234567890',
            'password' => Hash::make('password123'),
            'subscription_ends_at' => null,
        ]);
        $this->client->assignRole('client');

        Storage::fake('public');
        Storage::fake(PaymentProof::DISK);
    }

    public function test_guest_cannot_upload_payment_proof(): void
    {
        $response = $this->post('/client/payment-proofs', [
            'plan_name' => 'Paket Pro Bulanan',
            'amount' => 450000,
        ]);

        $response->assertRedirect('/login');
    }

    public function test_client_can_upload_valid_image_payment_proof(): void
    {
        $file = UploadedFile::fake()->image('bukti_transfer.jpg', 800, 600);

        $response = $this->actingAs($this->client)->post('/client/payment-proofs', [
            'plan_name' => 'Paket Pro Bulanan',
            'amount' => 450000,
            'bank_source' => 'BCA',
            'notes' => 'Pembayaran via m-BCA ke Rekening Fauzi',
            'proof_image' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payment_proofs', [
            'user_id' => $this->client->id,
            'plan_name' => 'Paket Pro Bulanan',
            'amount' => 450000,
            'bank_source' => 'BCA',
            'status' => 'pending',
            'uploaded_by_role' => 'client',
        ]);

        $proof = PaymentProof::where('user_id', $this->client->id)->first();
        $this->assertNotNull($proof);
        Storage::disk(PaymentProof::DISK)->assertExists($proof->proof_image);
        Storage::disk('public')->assertMissing($proof->proof_image);
    }

    public function test_client_strictly_cannot_upload_non_image_files(): void
    {
        // Try uploading a PDF
        $pdfFile = UploadedFile::fake()->create('dokumen_tagihan.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->client)->post('/client/payment-proofs', [
            'plan_name' => 'Paket Pro Bulanan',
            'amount' => 450000,
            'proof_image' => $pdfFile,
        ]);

        $response->assertSessionHasErrors(['proof_image']);
        $this->assertEquals(0, PaymentProof::count());

        // Try uploading a text file
        $txtFile = UploadedFile::fake()->create('rekening.txt', 50, 'text/plain');

        $response2 = $this->actingAs($this->client)->post('/client/payment-proofs', [
            'plan_name' => 'Paket Pro Bulanan',
            'amount' => 450000,
            'proof_image' => $txtFile,
        ]);

        $response2->assertSessionHasErrors(['proof_image']);
        $this->assertEquals(0, PaymentProof::count());
    }

    public function test_admin_can_upload_manual_payment_proof_with_auto_approve(): void
    {
        $file = UploadedFile::fake()->image('bukti_telegram.png', 600, 400);

        $response = $this->actingAs($this->superadmin)->post('/admin/payment-proofs', [
            'user_id' => $this->client->id,
            'plan_name' => 'Paket Pro Bulanan',
            'amount' => 450000,
            'bank_source' => 'Transfer Manual Telegram @FauziRahman95',
            'notes' => 'Konfirmasi chat Telegram',
            'auto_approve' => '1',
            'extend_days' => 30,
            'proof_image' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payment_proofs', [
            'user_id' => $this->client->id,
            'status' => 'approved',
            'uploaded_by_role' => 'admin',
        ]);

        // Client subscription should be activated for 30 days
        $this->client->refresh();
        $this->assertNotNull($this->client->subscription_ends_at);
        $this->assertTrue($this->client->subscription_ends_at->isFuture());
    }

    public function test_admin_strictly_cannot_upload_non_image_files(): void
    {
        $docFile = UploadedFile::fake()->create('slip_gaji.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $response = $this->actingAs($this->superadmin)->post('/admin/payment-proofs', [
            'user_id' => $this->client->id,
            'plan_name' => 'Paket Pro Bulanan',
            'amount' => 450000,
            'proof_image' => $docFile,
        ]);

        $response->assertSessionHasErrors(['proof_image']);
        $this->assertEquals(0, PaymentProof::count());
    }

    public function test_admin_can_approve_pending_payment_proof(): void
    {
        $file = UploadedFile::fake()->image('struk_atm.webp', 500, 500);

        // Upload as client first
        $this->actingAs($this->client)->post('/client/payment-proofs', [
            'plan_name' => 'Paket Pro Bulanan',
            'amount' => 450000,
            'bank_source' => 'ATM BCA',
            'proof_image' => $file,
        ]);

        $proof = PaymentProof::firstOrFail();
        $this->assertEquals('pending', $proof->status);

        // Approve as superadmin with 30 days extension
        $response = $this->actingAs($this->superadmin)->post("/admin/payment-proofs/{$proof->id}/approve", [
            'days' => 30,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $proof->refresh();
        $this->assertEquals('approved', $proof->status);
        $this->assertEquals($this->superadmin->id, $proof->verified_by);
        $this->assertNotNull($proof->verified_at);

        $this->client->refresh();
        $this->assertNotNull($this->client->subscription_ends_at);
    }

    public function test_admin_can_reject_payment_proof(): void
    {
        $file = UploadedFile::fake()->image('struk_buram.jpg', 500, 500);

        $this->actingAs($this->client)->post('/client/payment-proofs', [
            'plan_name' => 'Paket Pro Bulanan',
            'amount' => 450000,
            'proof_image' => $file,
        ]);

        $proof = PaymentProof::firstOrFail();

        // Reject as superadmin
        $response = $this->actingAs($this->superadmin)->post("/admin/payment-proofs/{$proof->id}/reject", [
            'admin_notes' => 'Nominal transfer tidak sesuai (hanya tertera Rp 45.000)',
        ]);

        $response->assertRedirect();
        $proof->refresh();
        $this->assertEquals('rejected', $proof->status);
        $this->assertEquals('Nominal transfer tidak sesuai (hanya tertera Rp 45.000)', $proof->admin_notes);
    }

    public function test_admin_can_delete_payment_proof(): void
    {
        $file = UploadedFile::fake()->image('bukti_hapus.jpg', 400, 400);

        $this->actingAs($this->client)->post('/client/payment-proofs', [
            'plan_name' => 'Paket Pro Bulanan',
            'amount' => 450000,
            'proof_image' => $file,
        ]);

        $proof = PaymentProof::firstOrFail();
        $path = $proof->proof_image;
        Storage::disk(PaymentProof::DISK)->assertExists($path);

        $response = $this->actingAs($this->superadmin)->delete("/admin/payment-proofs/{$proof->id}");
        $response->assertRedirect();

        $this->assertDatabaseMissing('payment_proofs', ['id' => $proof->id]);
        Storage::disk(PaymentProof::DISK)->assertMissing($path);
    }

    private function uploadProofAs(User $user): PaymentProof
    {
        $this->actingAs($user)->post('/client/payment-proofs', [
            'plan_name' => 'Paket Pro Bulanan',
            'amount' => 450000,
            'proof_image' => UploadedFile::fake()->image('bukti.png', 300, 300),
        ]);

        return PaymentProof::where('user_id', $user->id)->latest('id')->firstOrFail();
    }

    public function test_proof_url_points_to_protected_route_not_public_storage(): void
    {
        $proof = $this->uploadProofAs($this->client);

        $this->assertSame(route('payment-proofs.image', $proof), $proof->proof_url);
        $this->assertStringNotContainsString('/storage/', $proof->proof_url);
    }

    public function test_owner_can_view_own_payment_proof(): void
    {
        $proof = $this->uploadProofAs($this->client);

        $response = $this->actingAs($this->client)->get(route('payment-proofs.image', $proof));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_superadmin_can_view_any_payment_proof(): void
    {
        $proof = $this->uploadProofAs($this->client);

        $this->actingAs($this->superadmin)->get(route('payment-proofs.image', $proof))->assertOk();
    }

    public function test_other_client_cannot_view_payment_proof(): void
    {
        $proof = $this->uploadProofAs($this->client);

        $intruder = User::create([
            'name' => 'Klinik Lain',
            'email' => 'lain@klinik.id',
            'password' => Hash::make('password123'),
        ]);
        $intruder->assignRole('client');

        $this->actingAs($intruder)->get(route('payment-proofs.image', $proof))->assertNotFound();
    }

    public function test_guest_cannot_view_payment_proof(): void
    {
        $proof = $this->uploadProofAs($this->client);
        auth()->logout();

        $this->get(route('payment-proofs.image', $proof))->assertRedirect('/login');
    }
}
