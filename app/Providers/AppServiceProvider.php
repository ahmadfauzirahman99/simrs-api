<?php

namespace App\Providers;

use App\Http\Middleware\EnsureClientApiKey;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 1. Limiter Login: Max 5 percobaan per menit per email + IP (mencegah brute-force password)
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email');
            return Limit::perMinute(5)->by($email . '|' . $request->ip());
        });

        // 2. Limiter Registrasi: Max 5 pendaftaran per 10 menit per IP (mencegah bot spam akun klien)
        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinutes(10, 5)->by($request->ip());
        });

        // 3. Limiter Demo Login: Max 5 kali per menit per IP
        RateLimiter::for('demo', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // 4. Limiter Buat Ulang Token: Max 5 kali per menit per user
        RateLimiter::for('api-key-regenerate', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        // 5. Limiter API Gateway: Max 120 request per menit per CLIENT (mencegah flooding & DoS).
        //    Dihitung per pemilik token (Bearer maupun X-API-KEY), jadi beberapa client di satu IP
        //    tidak saling memblokir dan ganti token/IP tidak me-reset jatah. Token tidak valid -> per IP.
        RateLimiter::for('api', function (Request $request) {
            $clientId = EnsureClientApiKey::resolveToken($request)?->tokenable_id ?? $request->user()?->id;
            return Limit::perMinute(120)->by($clientId ? "client:{$clientId}" : "ip:{$request->ip()}");
        });

        // 6. Limiter Upload Bukti Transfer: Max 10 kali per 5 menit per user/IP (mencegah disk exhaustion DoS)
        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinutes(5, 10)->by($request->user()?->id ?: $request->ip());
        });

        // 7. Limiter Simulasi & Crypto: Max 60 request per menit (mencegah CPU exhaustion)
        RateLimiter::for('simulation', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // 8. Limiter Global: Max 180 request per menit per IP
        RateLimiter::for('global', function (Request $request) {
            return Limit::perMinute(180)->by($request->ip());
        });
    }
}
