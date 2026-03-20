<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Http;
use Tests\Feature\Api\Concerns\InteractsWithBpjsTestConfig;
use Tests\TestCase;

class BpjsListRencanaKontrolTanggalTest extends TestCase
{
    use InteractsWithBpjsTestConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureBpjsService();
    }

    public function test_list_rencana_kontrol_by_tanggal_endpoint_hits_bpjs_service(): void
    {
        Http::fake([
            'https://bpjs.test/*' => Http::response([
                'metaData' => [
                    'code' => '200',
                    'message' => 'Sukses',
                ],
                'response' => '',
            ], 200),
        ]);

        $response = $this->getJson('/api/v1/bpjs/surat-kontrol/list-tanggal?tgl_awal=2021-03-01&tgl_akhir=2021-03-31&filter=1');

        $response->assertOk()
            ->assertJsonPath('metaData.code', '200')
            ->assertJsonPath('metaData.message', 'Sukses');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://bpjs.test/vclaim-rest/RencanaKontrol/ListRencanaKontrol/tglAwal/2021-03-01/tglAkhir/2021-03-31/filter/1'
                && $request->method() === 'GET';
        });
    }

    public function test_list_rencana_kontrol_by_tanggal_requires_required_parameters(): void
    {
        $response = $this->getJson('/api/v1/bpjs/surat-kontrol/list-tanggal');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tgl_awal', 'tgl_akhir', 'filter']);
    }
}
