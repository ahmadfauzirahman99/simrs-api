<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Http;
use Tests\Feature\Api\Concerns\InteractsWithBpjsTestConfig;
use Tests\TestCase;

class BpjsListRencanaKontrolTest extends TestCase
{
    use InteractsWithBpjsTestConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureBpjsService();
    }

    public function test_list_rencana_kontrol_endpoint_hits_bpjs_service(): void
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

        $response = $this->getJson('/api/v1/bpjs/surat-kontrol/list?bulan=01&tahun=2022&no_kartu=0002035874204&filter=1');

        $response->assertOk()
            ->assertJsonPath('metaData.code', '200')
            ->assertJsonPath('metaData.message', 'Sukses');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://bpjs.test/vclaim-rest/RencanaKontrol/ListRencanaKontrol/Bulan/01/Tahun/2022/Nokartu/0002035874204/filter/1'
                && $request->method() === 'GET';
        });
    }

    public function test_list_rencana_kontrol_requires_required_parameters(): void
    {
        $response = $this->getJson('/api/v1/bpjs/surat-kontrol/list');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['bulan', 'tahun', 'no_kartu', 'filter']);
    }
}
