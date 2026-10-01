<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" href="/favicon.ico?v=2" sizes="48x48">
        <link rel="icon" href="/favicon.svg?v=2" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#115e59">
        <title inertia>{{ config('app.name', 'SIMRS Gateway - API Bridging BPJS & SATUSEHAT') }}</title>

        <meta name="description" content="SIMRS Gateway: Infrastruktur API Bridging BPJS Kesehatan (VClaim, Antrean RS, Apotek, PCare, Aplicares, Rekam Medis) dan Kemenkes SATUSEHAT FHIR R4. Sedia simulator mock sandbox dan lisensi source code on-premise.">
        <meta name="keywords" content="SIMRS, Bridging BPJS, BPJS Kesehatan, VClaim v2, Antrean RS, SATUSEHAT Kemenkes, FHIR R4, Rekam Medis Elektronik, Aplicares, PCare, Source Code SIMRS, API Gateway Rumah Sakit">
        <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
        <link rel="canonical" href="{{ url()->current() }}">

        <!-- Open Graph / WhatsApp / Facebook -->
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:title" content="SIMRS Gateway - Solusi Bridging BPJS Kesehatan & SATUSEHAT">
        <meta property="og:description" content="Integrasi cepat SIMRS ke BPJS Kesehatan dan SATUSEHAT. Otomatisasi enkripsi AES-256, HMAC-SHA256, dan dekripsi LZ-string. Tersedia simulator & lisensi source code RS.">
        <meta property="og:locale" content="id_ID">
        <meta property="og:site_name" content="SIMRS Gateway">
        <meta property="og:image" content="{{ url('/images/og-image.png') }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="SIMRS Gateway: SIMRS ke Gateway ke BPJS dan SATUSEHAT">

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="SIMRS Gateway - API Bridging BPJS & SATUSEHAT">
        <meta name="twitter:description" content="Infrastruktur API Bridging BPJS Kesehatan & SATUSEHAT FHIR R4 untuk Rumah Sakit, Klinik, dan Vendor SIMRS.">
        <meta name="twitter:image" content="{{ url('/images/og-image.png') }}">

        <!-- Schema.org JSON-LD Structured Data for Google Rich Snippets -->
        <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => 'SIMRS Gateway BPJS & SATUSEHAT',
            'operatingSystem' => 'Linux, Windows, Web, Intranet RS',
            'applicationCategory' => 'HealthApplication',
            'description' => 'API Gateway dan Simulator BPJS Kesehatan (VClaim, Antrean RS, Apotek) dan SATUSEHAT FHIR R4 untuk Rumah Sakit dan Vendor SIMRS.',
            'offers' => [
                '@type' => 'Offer',
                'price' => (string) \App\Models\SiteSetting::get('pricing_monthly_price', 450000),
                'priceCurrency' => 'IDR',
                'availability' => 'https://schema.org/InStock',
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_HEX_TAG) !!}
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="min-h-screen bg-[var(--app-canvas)] text-[var(--app-ink)] antialiased">
        @inertia
    </body>
</html>
