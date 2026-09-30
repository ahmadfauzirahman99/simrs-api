<?php

namespace Tests\Feature\Web;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_access_landing_page(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    }
}
