<?php

namespace Tests\Feature\Api\Concerns;

trait InteractsWithBpjsTestConfig
{
    protected function configureBpjsService(): void
    {
        config([
            'services.bpjs_v3.api_ver' => '3',
            'services.bpjs_v3.base_url' => 'https://bpjs.test/vclaim-rest',
            'services.bpjs_v3.cons_id' => 'demo-cons-id',
            'services.bpjs_v3.secret_key' => 'demo-secret',
            'services.bpjs_v3.user_key' => 'demo-user-key',
        ]);
    }
}
