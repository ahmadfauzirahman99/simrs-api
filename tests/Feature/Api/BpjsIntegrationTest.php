<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Http;
use Tests\Feature\Api\Concerns\InteractsWithBpjsPayloads;
use Tests\Feature\Api\Concerns\InteractsWithBpjsTestConfig;
use Tests\TestCase;

class BpjsIntegrationTest extends TestCase
{
    use InteractsWithBpjsPayloads;
    use InteractsWithBpjsTestConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureBpjsService();
    }

    public function test_peserta_endpoint_sends_request_to_bpjs_service(): void
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

        $response = $this->getJson('/api/v1/bpjs/peserta?nomor=0001234567890&tipe=1');

        $response->assertOk()
            ->assertJsonPath('metaData.code', '200')
            ->assertJsonPath('metaData.message', 'Sukses');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://bpjs.test/vclaim-rest/Peserta/nokartu/0001234567890/tglSEP/' . date('Y-m-d')
                && $request->method() === 'GET'
                && $request->hasHeader('X-cons-id', 'demo-cons-id')
                && $request->hasHeader('user_key', 'demo-user-key')
                && $request->hasHeader('X-signature');
        });
    }

    public function test_peserta_endpoint_returns_bpjs_error_response(): void
    {
        Http::fake([
            'https://bpjs.test/*' => Http::response([
                'metaData' => [
                    'code' => '400',
                    'message' => 'Data peserta tidak ditemukan',
                ],
                'response' => null,
            ], 200),
        ]);

        $response = $this->getJson('/api/v1/bpjs/peserta?nomor=0001234567890&tipe=1');

        $response->assertStatus(400)
            ->assertJsonPath('metaData.code', '400')
            ->assertJsonPath('metaData.message', 'Data peserta tidak ditemukan');
    }

    public function test_insert_sep_endpoint_sends_payload_to_bpjs_service(): void
    {
        Http::fake([
            'https://bpjs.test/*' => Http::response([
                'metaData' => [
                    'code' => '200',
                    'message' => 'SEP berhasil dibuat',
                ],
                'response' => '',
            ], 200),
        ]);

        $payload = $this->sepInsertPayload();

        $response = $this->postJson('/api/v1/bpjs/sep', $payload);

        $response->assertOk()
            ->assertJsonPath('metaData.code', '200')
            ->assertJsonPath('metaData.message', 'SEP berhasil dibuat');

        Http::assertSent(function ($request) {
            $body = $request->body();

            return $request->url() === 'https://bpjs.test/vclaim-rest/SEP/2.0/insert'
                && $request->method() === 'POST'
                && str_contains($body, '"noKartu":"0001234567890"')
                && str_contains($body, '"tujuan":"INT"');
        });
    }

    public function test_update_sep_endpoint_returns_bpjs_error_response(): void
    {
        Http::fake([
            'https://bpjs.test/*' => Http::response([
                'metaData' => [
                    'code' => '500',
                    'message' => 'Gagal update SEP',
                ],
                'response' => null,
            ], 200),
        ]);

        $response = $this->putJson('/api/v1/bpjs/sep', $this->sepUpdatePayload());

        $response->assertStatus(500)
            ->assertJsonPath('metaData.code', '500')
            ->assertJsonPath('metaData.message', 'Gagal update SEP');
    }

    public function test_delete_sep_endpoint_sends_delete_request_to_bpjs_service(): void
    {
        Http::fake([
            'https://bpjs.test/*' => Http::response([
                'metaData' => [
                    'code' => '200',
                    'message' => 'SEP berhasil dihapus',
                ],
                'response' => '',
            ], 200),
        ]);

        $response = $this->deleteJson('/api/v1/bpjs/sep', $this->sepDeletePayload());

        $response->assertOk()
            ->assertJsonPath('metaData.code', '200')
            ->assertJsonPath('metaData.message', 'SEP berhasil dihapus');

        Http::assertSent(function ($request) {
            $body = $request->body();

            return $request->url() === 'https://bpjs.test/vclaim-rest/SEP/Delete'
                && $request->method() === 'DELETE'
                && str_contains($body, '"noSep":"0301R0110326V000001"');
        });
    }

    public function test_insert_surat_kontrol_endpoint_sends_payload_to_bpjs_service(): void
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

        $response = $this->postJson('/api/v1/bpjs/surat-kontrol/insert', $this->suratKontrolInsertPayload());

        $response->assertOk()
            ->assertJsonPath('metaData.code', '200')
            ->assertJsonPath('metaData.message', 'Sukses');

        Http::assertSent(function ($request) {
            $body = $request->body();

            return $request->url() === 'https://bpjs.test/vclaim-rest/RencanaKontrol/insert'
                && $request->method() === 'POST'
                && str_contains($body, '"noSEP":"0301R0111018V000006"')
                && str_contains($body, '"kodeDokter":"12345"');
        });
    }

    public function test_update_surat_kontrol_endpoint_returns_bpjs_error_response(): void
    {
        Http::fake([
            'https://bpjs.test/*' => Http::response([
                'metaData' => [
                    'code' => '400',
                    'message' => 'Nomor surat kontrol tidak valid',
                ],
                'response' => null,
            ], 200),
        ]);

        $response = $this->postJson('/api/v1/bpjs/surat-kontrol/update', $this->suratKontrolUpdatePayload());

        $response->assertStatus(400)
            ->assertJsonPath('metaData.code', '400')
            ->assertJsonPath('metaData.message', 'Nomor surat kontrol tidak valid');
    }
}
