<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Http;
use Tests\Feature\Api\Concerns\InteractsWithBpjsTestConfig;
use Tests\TestCase;

class BpjsSuratKontrolDetailTest extends TestCase
{
    use InteractsWithBpjsTestConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureBpjsService();
    }

    public function test_cari_nomor_surat_kontrol_endpoint_hits_bpjs_service(): void
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

        $response = $this->getJson('/api/v1/bpjs/surat-kontrol/detail?no_surat_kontrol=0301R0111125K000002');

        $response->assertOk()
            ->assertJsonPath('metaData.code', '200')
            ->assertJsonPath('metaData.message', 'Sukses');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://bpjs.test/vclaim-rest/RencanaKontrol/noSuratKontrol/0301R0111125K000002'
                && $request->method() === 'GET';
        });
    }

    public function test_cari_nomor_surat_kontrol_requires_parameter(): void
    {
        $response = $this->getJson('/api/v1/bpjs/surat-kontrol/detail');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['no_surat_kontrol']);
    }
}
