<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Http;
use Tests\Feature\Api\Concerns\InteractsWithBpjsTestConfig;
use Tests\TestCase;

class BpjsSuratKontrolSearchTest extends TestCase
{
    use InteractsWithBpjsTestConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureBpjsService();
    }

    public function test_cari_sep_surat_kontrol_endpoint_hits_bpjs_service(): void
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

        $response = $this->getJson('/api/v1/bpjs/surat-kontrol/sep?no_sep=0301R0010819V006059');

        $response->assertOk()
            ->assertJsonPath('metaData.code', '200')
            ->assertJsonPath('metaData.message', 'Sukses');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://bpjs.test/vclaim-rest/RencanaKontrol/nosep/0301R0010819V006059'
                && $request->method() === 'GET';
        });
    }

    public function test_cari_sep_surat_kontrol_requires_no_sep(): void
    {
        $response = $this->getJson('/api/v1/bpjs/surat-kontrol/sep');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['no_sep']);
    }
}
