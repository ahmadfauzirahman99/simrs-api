<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Http;
use Tests\Feature\Api\Concerns\InteractsWithBpjsPayloads;
use Tests\Feature\Api\Concerns\InteractsWithBpjsTestConfig;
use Tests\TestCase;

class BpjsSpriIntegrationTest extends TestCase
{
    use InteractsWithBpjsPayloads;
    use InteractsWithBpjsTestConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureBpjsService();
    }

    public function test_insert_spri_endpoint_sends_payload_to_bpjs_service(): void
    {
        Http::fake([
            'https://bpjs.test/*' => Http::response([
                'metaData' => ['code' => '200', 'message' => 'Sukses'],
                'response' => '',
            ], 200),
        ]);

        $response = $this->postJson('/api/v1/bpjs/spri/insert', $this->spriInsertPayload());

        $response->assertOk()
            ->assertJsonPath('metaData.code', '200');

        Http::assertSent(function ($request) {
            $body = $request->body();

            return $request->url() === 'https://bpjs.test/vclaim-rest/RencanaKontrol/insertSPRI'
                && $request->method() === 'POST'
                && str_contains($body, '"noKartu":"0001234567890"')
                && str_contains($body, '"kodeDokter":"12345"');
        });
    }

    public function test_update_spri_endpoint_returns_bpjs_error_response(): void
    {
        Http::fake([
            'https://bpjs.test/*' => Http::response([
                'metaData' => ['code' => '400', 'message' => 'Nomor SPRI tidak valid'],
                'response' => null,
            ], 200),
        ]);

        $response = $this->postJson('/api/v1/bpjs/spri/update', $this->spriUpdatePayload());

        $response->assertStatus(400)
            ->assertJsonPath('metaData.message', 'Nomor SPRI tidak valid');
    }
}
