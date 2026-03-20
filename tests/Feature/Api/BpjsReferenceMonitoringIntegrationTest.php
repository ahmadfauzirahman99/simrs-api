<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Http;
use Tests\Feature\Api\Concerns\InteractsWithBpjsTestConfig;
use Tests\TestCase;

class BpjsReferenceMonitoringIntegrationTest extends TestCase
{
    use InteractsWithBpjsTestConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureBpjsService();
    }

    public function test_referensi_poli_endpoint_hits_bpjs_service(): void
    {
        Http::fake([
            'https://bpjs.test/*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'Sukses'],
                'response' => '',
            ], 200),
        ]);

        $response = $this->getJson('/api/v1/bpjs/referensi/poli?keyword=int');

        $response->assertOk()
            ->assertJsonPath('metaData.code', '200');

        Http::assertSent(fn($request) => $request->url() === 'https://bpjs.test/vclaim-rest/referensi/poli/int'
            && $request->method() === 'GET');
    }

    public function test_referensi_faskes_endpoint_passes_keyword_and_type(): void
    {
        Http::fake([
            'https://bpjs.test/*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'Sukses'],
                'response' => '',
            ], 200),
        ]);

        $response = $this->getJson('/api/v1/bpjs/referensi/faskes?keyword=rsud&tipe=2');

        $response->assertOk();

        Http::assertSent(fn($request) => $request->url() === 'https://bpjs.test/vclaim-rest/referensi/faskes/rsud/2'
            && $request->method() === 'GET');
    }

    public function test_referensi_dokter_dpjp_returns_bpjs_error_response(): void
    {
        Http::fake([
            'https://bpjs.test/*' => Http::response([
                'metaData' => ['code' => '404', 'message' => 'Dokter tidak ditemukan'],
                'response' => null,
            ], 200),
        ]);

        $response = $this->getJson('/api/v1/bpjs/referensi/dokter-dpjp?kode=INT&jenis=2&tgl=2026-03-19');

        $response->assertStatus(404)
            ->assertJsonPath('metaData.message', 'Dokter tidak ditemukan');
    }

    public function test_monitoring_kunjungan_endpoint_hits_bpjs_service(): void
    {
        Http::fake([
            'https://bpjs.test/*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'Sukses'],
                'response' => '',
            ], 200),
        ]);

        $response = $this->getJson('/api/v1/bpjs/monitoring-kunjungan?tanggal=2026-03-19&tipe=2');

        $response->assertOk();

        Http::assertSent(fn($request) => $request->url() === 'https://bpjs.test/vclaim-rest/Monitoring/Kunjungan/Tanggal/2026-03-19/JnsPelayanan/2'
            && $request->method() === 'GET');
    }

    public function test_monitoring_klaim_endpoint_returns_bpjs_error_response(): void
    {
        Http::fake([
            'https://bpjs.test/*' => Http::response([
                'metaData' => ['code' => '500', 'message' => 'Monitoring klaim gagal'],
                'response' => null,
            ], 200),
        ]);

        $response = $this->getJson('/api/v1/bpjs/monitoring-klaim?tanggal=2026-03-19&tipe=2&status=1');

        $response->assertStatus(500)
            ->assertJsonPath('metaData.message', 'Monitoring klaim gagal');
    }
}
