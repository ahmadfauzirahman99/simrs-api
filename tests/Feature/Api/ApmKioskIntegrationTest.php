<?php

namespace Tests\Feature\Api;

use App\Models\Antrean;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ApmKioskIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_apm_kiosk_page_can_be_accessed(): void
    {
        $response = $this->get('/apm');

        $response->assertOk()
            ->assertInertia(fn ($page) => $page->component('Apm/Kiosk'));
    }

    public function test_jadwal_hari_ini_returns_list_of_poliklinik(): void
    {
        $response = $this->getJson('/api/apm-api/jadwal-hari-ini');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['kdpoli', 'nmpoli', 'dokter'],
            ],
        ]);
    }

    public function test_daftar_mandiri_creates_antrean_and_tasks(): void
    {
        $payload = [
            'jenispasien' => 'JKN',
            'kodepoli' => 'INT',
            'namapoli' => 'PENYAKIT DALAM',
            'kodedokter' => '101',
            'namadokter' => 'dr. Budi Santoso, Sp.PD',
            'jampraktek' => '08:00-12:00',
            'nomorkartu' => '0001234567890',
            'nik' => '3201234567890001',
            'nama_pasien' => 'Ahmad Fauzi',
            'nohp' => '081299998888',
        ];

        $response = $this->postJson('/api/apm-api/daftar-mandiri', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'jenispasien' => 'JKN',
                'kodepoli' => 'INT',
                'nomorantrean' => 'INT-001',
                'angkaantrean' => 1,
                'status' => 'Check-In',
            ],
        ]);

        $this->assertDatabaseHas('antreans', [
            'nomorkartu' => '0001234567890',
            'kodepoli' => 'INT',
            'nomorantrean' => 'INT-001',
            'status' => 'Check-In',
        ]);
    }

    public function test_check_in_mobile_jkn_booking_updates_task_3(): void
    {
        $today = Carbon::today()->toDateString();
        $antrean = Antrean::create([
            'kode_booking' => 'BKG-20261025-001',
            'jenispasien' => 'JKN',
            'nomorkartu' => '0009998887771',
            'nik' => '3201999888777001',
            'nama_pasien' => 'Siti Nurhaliza',
            'kodepoli' => 'ANA',
            'namapoli' => 'ANAK',
            'kodedokter' => '201',
            'namadokter' => 'dr. Hendra Wijaya, Sp.A',
            'tanggalperiksa' => $today,
            'nomorantrean' => 'ANA-002',
            'angkaantrean' => 2,
            'status' => 'Menunggu',
            'sumber' => 'Mobile JKN',
        ]);

        $response = $this->postJson('/api/apm-api/check-in', [
            'kodebooking' => 'BKG-20261025-001',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'kode_booking' => 'BKG-20261025-001',
                'status' => 'Check-In',
            ],
        ]);

        $antrean->refresh();
        $this->assertEquals('Check-In', $antrean->status);
        $this->assertNotNull($antrean->task3_waktu);

        // Check-in lagi harus memberi notif already_checked_in
        $second = $this->postJson('/api/apm-api/check-in', [
            'kodebooking' => 'BKG-20261025-001',
        ]);
        $second->assertStatus(200);
        $second->assertJson([
            'already_checked_in' => true,
        ]);
    }

    public function test_update_task_id_progresses_service_status(): void
    {
        $antrean = Antrean::create([
            'kode_booking' => 'BKG-TASK-001',
            'jenispasien' => 'JKN',
            'kodepoli' => 'BED',
            'namapoli' => 'BEDAH',
            'kodedokter' => '301',
            'namadokter' => 'dr. Agus Supriatna',
            'tanggalperiksa' => Carbon::today()->toDateString(),
            'nomorantrean' => 'BED-001',
            'status' => 'Check-In',
        ]);

        // Task 4: Dipanggil Poli Dokter
        $resTask4 = $this->postJson('/api/apm-api/update-task', [
            'kodebooking' => 'BKG-TASK-001',
            'taskid' => 4,
        ]);
        $resTask4->assertStatus(200);
        $antrean->refresh();
        $this->assertEquals('Dipanggil Poli', $antrean->status);
        $this->assertNotNull($antrean->task4_waktu);

        // Task 7: Selesai Layanan Farmasi
        $resTask7 = $this->postJson('/api/apm-api/update-task', [
            'kodebooking' => 'BKG-TASK-001',
            'taskid' => 7,
        ]);
        $resTask7->assertStatus(200);
        $antrean->refresh();
        $this->assertEquals('Selesai', $antrean->status);
        $this->assertNotNull($antrean->task7_waktu);
    }
}
