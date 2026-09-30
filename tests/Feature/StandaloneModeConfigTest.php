<?php

namespace Tests\Feature;

use Tests\TestCase;

class StandaloneModeConfigTest extends TestCase
{
    public function test_middleware_reads_standalone_mode_from_config_not_env(): void
    {
        // Setelah `config:cache`, env() di luar folder config selalu null.
        // Middleware harus membaca config('app.standalone') agar lisensi on-premise tidak kena kuota trial.
        foreach (['EnsureActiveSubscription', 'EnsureClientApiKey'] as $middleware) {
            $source = file_get_contents(app_path("Http/Middleware/{$middleware}.php"));
            $this->assertStringNotContainsString("env('STANDALONE_MODE'", $source, $middleware);
        }

        config(['app.standalone' => true]);
        $this->assertTrue(config('app.standalone'));
    }
}
