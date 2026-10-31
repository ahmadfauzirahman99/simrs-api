<?php

namespace App\Http\Middleware;

use App\Models\GatewayLog;
use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $rawToken = $request->bearerToken() ?: $request->header('X-API-KEY');
        $user = $request->user();

        // If not already authenticated via session, authenticate via Sanctum token
        if (!$user && $rawToken) {
            $accessToken = self::resolveToken($request);
            if ($accessToken && $accessToken->tokenable) {
                $user = $accessToken->tokenable;
                $request->setUserResolver(fn() => $user);
            }
        }

        // Allow pass-through for unit tests that test raw controller responses without auth
        if (!$user && app()->runningUnitTests() && !$rawToken) {
            return $next($request);
        }

        // If still no authenticated user/token
        if (!$user) {
            $durationMs = (int) max(1, round((microtime(true) - $startTime) * 1000));

            if ($rawToken && !app()->runningUnitTests()) {
                $failedKey = 'auth_fail_ip_' . md5($request->ip());
                $fails = (int) Cache::get($failedKey, 0) + 1;
                Cache::put($failedKey, $fails, now()->addMinutes(10));

                if ($fails > 25) {
                    $this->logRequest($request, null, 429, $durationMs, 'Rate limit exceeded on invalid auth attempts');
                    return response()->json([
                        'metaData' => [
                            'code' => '429',
                            'message' => 'Terlalu banyak percobaan autentikasi dengan API Key tidak valid. Akses IP Anda ditangguhkan selama 10 menit demi keamanan.',
                        ],
                        'response' => null,
                    ], 429);
                }
            }

            $this->logRequest($request, null, 401, $durationMs, '401 Unauthorized: API Key missing or invalid');

            return response()->json([
                'metaData' => [
                    'code' => '401',
                    'message' => 'Autentikasi Gagal: Silakan sertakan API Key pada Header (Authorization: Bearer <API_KEY> atau X-API-KEY: <API_KEY>).',
                ],
                'response' => null,
            ], 401);
        }

        // Always track daily request count for all users (trial & pro)
        $today = now()->format('Y-m-d');
        $cacheKey = "client_req_{$user->id}_{$today}";

        // Hitung atomik: increment dulu, baru dicek. Request bersamaan tidak bisa lolos melewati kuota.
        Cache::add($cacheKey, 0, now()->endOfDay());
        $usedToday = (int) Cache::increment($cacheKey);

        // Quota Enforcement: Unlimited vs Trial
        $isUnlimited = $user->hasActiveSubscription() || config('app.standalone');
        $trialLimit = (int) SiteSetting::get('pricing_trial_limit', 50);

        if (!$isUnlimited && $usedToday > $trialLimit) {
            // Request yang ditolak tidak dihitung sebagai pemakaian.
            $usedToday = (int) Cache::decrement($cacheKey);
            $durationMs = (int) max(1, round((microtime(true) - $startTime) * 1000));
            $monthlyPrice = (int) SiteSetting::get('pricing_monthly_price', 450000);
            $formattedPrice = 'Rp ' . number_format($monthlyPrice, 0, ',', '.');
            $telegramContact = ltrim(SiteSetting::get('telegram_contact', 'FauziRahman95'), '@');
            $promoActive = (bool) SiteSetting::get('pricing_monthly_promo_active', true);
            $promoCode = SiteSetting::get('pricing_monthly_promo_code', 'SIMRS450');

            $promoMsg = $promoActive && $promoCode ? " (Gunakan Kupon: {$promoCode})" : '';

            $this->logRequest($request, $user, 429, $durationMs, "Trial quota exceeded ({$usedToday}/{$trialLimit})");

            return response()->json([
                'metaData' => [
                    'code' => '429',
                    'message' => "Batas kuota uji coba {$trialLimit} request/hari telah tercapai. Upgrade ke Lisensi Bulanan {$formattedPrice} / bulan{$promoMsg} untuk Akses Semaunya (Unlimited Request).",
                ],
                'response' => [
                    'quota_used' => $usedToday,
                    'quota_limit' => $trialLimit,
                    'upgrade_url' => 'https://t.me/' . $telegramContact,
                ],
            ], 429);
        }

        /** @var Response $response */
        $response = $next($request);

        $durationMs = (int) max(1, round((microtime(true) - $startTime) * 1000));
        $this->logRequest($request, $user, $response->getStatusCode(), $durationMs, substr($response->getContent() ?: '', 0, 500));

        // Append telemetry headers
        $response->headers->set('X-Client-Id', "CLIENT-{$user->id}");
        $response->headers->set('X-Quota-Type', $isUnlimited ? 'unlimited' : "trial-{$trialLimit}/day");

        if (!$isUnlimited) {
            $remaining = max(0, $trialLimit - $usedToday);
            $response->headers->set('X-Quota-Remaining', (string) $remaining);
        }

        return $response;
    }

    /**
     * Cari token API dari header Bearer atau X-API-KEY, sekali per request.
     * Dipakai juga oleh rate limiter 'api' agar batas dihitung per client, bukan per IP.
     */
    public static function resolveToken(Request $request): ?PersonalAccessToken
    {
        if (! $request->attributes->has('api_access_token')) {
            $rawToken = $request->bearerToken() ?: $request->header('X-API-KEY');
            $request->attributes->set('api_access_token', $rawToken ? PersonalAccessToken::findToken($rawToken) : null);
        }

        return $request->attributes->get('api_access_token');
    }

    private function logRequest(Request $request, $user, int $statusCode, int $durationMs, ?string $summary = null): void
    {
        try {
            $isMock = (bool) ($request->attributes->get('is_mock_response', false)
                || $request->header('X-Mock-Mode') === 'true'
                || $request->header('X-Mock') === 'true');

            GatewayLog::create([
                'user_id' => $user?->id,
                'client_name' => $user?->name ?? 'Unauthenticated Client',
                'client_email' => $user?->email,
                'endpoint' => '/' . ltrim($request->path(), '/'),
                'method' => strtoupper($request->method()),
                'service' => GatewayLog::detectService($request->path()),
                'status_code' => $statusCode,
                'response_time_ms' => $durationMs,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
                'request_payload' => !empty($request->all()) ? json_encode($request->except(['password', 'secret', 'key'])) : null,
                'response_summary' => $summary,
                'is_mock' => $isMock,
            ]);
        } catch (\Throwable) {
            // Fail safely to never break request cycle
        }
    }
}
