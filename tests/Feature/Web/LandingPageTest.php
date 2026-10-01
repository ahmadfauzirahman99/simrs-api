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
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertSee('meta name="description"', false)
            ->assertSee('application/ld+json', false);
    }

    public function test_robots_txt_and_sitemap_are_accessible_for_crawlers(): void
    {
        // Protokol sitemap & robots mewajibkan URL absolut; relatif ditolak Google Search Console.
        $robots = $this->get('/robots.txt');
        $robots->assertOk()
            ->assertSee('Sitemap: ' . url('/sitemap.xml'))
            ->assertSee('Disallow: /admin/');

        $sitemap = $this->get('/sitemap.xml');
        $sitemap->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee('<loc>' . url('/register') . '</loc>', false)
            ->assertDontSee('<loc>/', false);

        $this->assertNotFalse(simplexml_load_string($sitemap->getContent()), 'sitemap harus XML valid');
    }

    public function test_landing_has_absolute_og_image_for_link_previews(): void
    {
        $image = url('/images/og-image.png');

        $this->get('/')
            ->assertSee('<meta property="og:image" content="' . $image . '">', false)
            ->assertSee('<meta property="og:image:width" content="1200">', false)
            ->assertSee('<meta property="og:image:height" content="630">', false)
            ->assertSee('<meta name="twitter:image" content="' . $image . '">', false);

        $this->assertFileExists(public_path('images/og-image.png'));
        [$width, $height] = getimagesize(public_path('images/og-image.png'));
        $this->assertSame([1200, 630], [$width, $height]);
    }

    public function test_favicon_set_is_linked_and_not_empty(): void
    {
        $this->get('/')
            ->assertSee('<link rel="icon" href="/favicon.ico?v=2" sizes="48x48">', false)
            ->assertSee('<link rel="icon" href="/favicon.svg?v=2" type="image/svg+xml">', false)
            ->assertSee('<link rel="apple-touch-icon" href="/apple-touch-icon.png">', false)
            ->assertSee('<link rel="manifest" href="/manifest.json">', false)
            ->assertSee('<meta name="theme-color" content="#115e59">', false);

        // favicon.ico bawaan Laravel berukuran 0 byte; pastikan tidak kembali kosong.
        foreach (['favicon.ico', 'favicon.svg', 'apple-touch-icon.png', 'icon-192.png', 'icon-512.png'] as $file) {
            $this->assertGreaterThan(0, filesize(public_path($file)), $file);
        }
        $manifest = json_decode(file_get_contents(public_path('manifest.json')), true);
        $this->assertSame(['192x192', '512x512'], array_column($manifest['icons'], 'sizes'));
    }

    public function test_seo_static_files_do_not_shadow_dynamic_routes(): void
    {
        // nginx menyajikan file statis lebih dulu; file ini akan membuat route dinamis tak pernah terpanggil.
        $this->assertFileDoesNotExist(public_path('sitemap.xml'));
        $this->assertFileDoesNotExist(public_path('robots.txt'));
    }

    public function test_landing_has_single_meta_description_and_price_follows_settings(): void
    {
        \App\Models\SiteSetting::set('pricing_monthly_price', 525000);

        $html = $this->get('/')->getContent();

        $this->assertSame(1, substr_count($html, 'name="description"'));
        // <Head> Vue dipasang di browser (tanpa SSR), jadi cek sumbernya: meta situs cukup di app.blade.php.
        $landing = file_get_contents(resource_path('js/Pages/Landing.vue'));
        $this->assertStringNotContainsString('name="description"', $landing);
        $this->assertStringNotContainsString('property="og:', $landing);
        $this->assertStringContainsString('"price": "525000"', $html);
    }
}
