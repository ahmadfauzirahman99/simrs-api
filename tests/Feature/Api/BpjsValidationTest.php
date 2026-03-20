<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class BpjsValidationTest extends TestCase
{
    public function test_insert_sep_requires_request_t_sep_payload(): void
    {
        $response = $this->postJson('/api/v1/bpjs/sep', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['request', 'request.t_sep']);
    }

    public function test_update_sep_requires_no_sep_and_user(): void
    {
        $response = $this->putJson('/api/v1/bpjs/sep', [
            'request' => [
                't_sep' => [],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['request.t_sep.noSep', 'request.t_sep.user']);
    }

    public function test_delete_sep_requires_no_sep_and_user(): void
    {
        $response = $this->deleteJson('/api/v1/bpjs/sep', [
            'request' => [
                't_sep' => [],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['request.t_sep.noSep', 'request.t_sep.user']);
    }
}
