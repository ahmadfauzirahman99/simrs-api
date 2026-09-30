<?php

namespace Tests\Feature\Web;

use Tests\TestCase;

class LoginPageNoDefaultCredentialsTest extends TestCase
{
    public function test_login_page_does_not_expose_default_superadmin_credentials(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertDontSee('admin@simrs.local');
        $response->assertDontSee('defaultPassword');
        $response->assertDontSee('defaultEmail');

        $source = file_get_contents(resource_path('js/Pages/Auth/Login.vue'));
        $this->assertStringNotContainsString('Akun Default', $source);
        $this->assertStringNotContainsString('Super Admin', $source);
        $this->assertStringNotContainsString('Administrator', $source);
    }

    public function test_frontend_bundle_sources_do_not_contain_default_admin_email(): void
    {
        // Semua halaman Vue dibundel ke satu file JS publik, jadi tidak boleh ada yang menyebut akun default.
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('js')));

        foreach ($files as $file) {
            if (! in_array($file->getExtension(), ['vue', 'js'], true)) {
                continue;
            }
            $source = file_get_contents($file->getPathname());
            $this->assertStringNotContainsString('admin@simrs.local', $source, $file->getPathname());
            $this->assertStringNotContainsString('Super Administrator', $source, $file->getPathname());
        }
    }
}
