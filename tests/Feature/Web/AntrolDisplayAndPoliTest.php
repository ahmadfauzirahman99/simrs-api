<?php

namespace Tests\Feature\Web;

use App\Models\Antrean;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AntrolDisplayAndPoliTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'superadmin']);
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('superadmin');
    }

    public function test_display_antrean_page_can_be_accessed(): void
    {
        $response = $this->get('/display/antrean');
        $response->assertStatus(200);
    }

    public function test_display_state_api_returns_active_queue_and_last_call(): void
    {
        Antrean::create([
            'kode_booking' => 'BK2610A',
            'jenispasien' => 'JKN',
            'kodepoli' => 'INT',
            'namapoli' => 'Penyakit Dalam',
            'kodedokter' => '101',
            'namadokter' => 'dr. Budi Santoso',
            'tanggalperiksa' => now()->toDateString(),
            'nomorantrean' => 'A-001',
            'status' => 'Dipanggil Poli',
        ]);

        $response = $this->getJson('/api/apm-api/display-state');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'active_calls',
                'last_call',
                'timestamp',
            ],
        ]);
    }

    public function test_poli_panggil_screen_and_calling_actions(): void
    {
        $antrean = Antrean::create([
            'kode_booking' => 'BK2610B',
            'jenispasien' => 'JKN',
            'kodepoli' => 'INT',
            'namapoli' => 'Penyakit Dalam',
            'kodedokter' => '101',
            'namadokter' => 'dr. Budi Santoso',
            'tanggalperiksa' => now()->toDateString(),
            'nomorantrean' => 'A-002',
            'status' => 'Check-In',
        ]);

        $response = $this->get('/poli/panggil');
        $response->assertStatus(200);

        // Panggil antrean
        $callResponse = $this->post("/poli/panggil/{$antrean->id}/panggil");
        $callResponse->assertRedirect();

        $antrean->refresh();
        $this->assertEquals('Dipanggil Poli', $antrean->status);
        $this->assertNotNull($antrean->task4_waktu);

        // Selesai pelayanan
        $doneResponse = $this->post("/poli/panggil/{$antrean->id}/selesai");
        $doneResponse->assertRedirect();

        $antrean->refresh();
        $this->assertEquals('Selesai Layanan Dokter', $antrean->status);
        $this->assertNotNull($antrean->task5_waktu);
    }

    public function test_admin_sep_page_and_generator(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get('/admin/sep');
        $response->assertStatus(200);

        // Search SEP
        $searchResponse = $this->postJson('/admin/sep/search', [
            'no_sep' => '0112R0011026V000123',
        ]);
        $searchResponse->assertStatus(200);
        $searchResponse->assertJson(['success' => true]);

        // Generate SEP
        $generateResponse = $this->postJson('/admin/sep/generate', [
            'no_kartu' => '0001827364521',
            'nama_pasien' => 'Ny. Rahmawati',
            'kodepoli' => 'INT',
            'namapoli' => 'Penyakit Dalam',
            'kodedokter' => '101',
            'namadokter' => 'dr. Budi',
            'diagnosa' => 'E11.9',
        ]);
        $generateResponse->assertStatus(200);
        $generateResponse->assertJson(['success' => true]);
    }

    public function test_simrs_sync_command_runs_successfully(): void
    {
        $this->artisan('simrs:sync-antrol', ['--dry-run' => true])
            ->assertExitCode(0);
    }
}
