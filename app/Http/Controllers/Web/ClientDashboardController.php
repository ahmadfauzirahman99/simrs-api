<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class ClientDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Ensure user has at least one active token
        $latestToken = $user->tokens()->latest()->first();
        $apiKey = session('new_api_key');

        if (!$apiKey && $latestToken) {
            // Display masked format if existing
            $apiKey = 'simrs_' . substr(md5($latestToken->id . $user->id), 0, 8) . '...' . substr($latestToken->token, -6);
        } elseif (!$apiKey && !$latestToken) {
            $plain = $user->createToken('default-key')->plainTextToken;
            $apiKey = $plain;
        }

        // Calculate today's quota usage from both cache and persistent gateway logs
        $today = now()->format('Y-m-d');
        $todayStart = now()->startOfDay();
        $cacheKey = "client_req_{$user->id}_{$today}";
        $dbQuotaUsed = \App\Models\GatewayLog::where('user_id', $user->id)
            ->where('created_at', '>=', $todayStart)
            ->count();
        $quotaUsed = max((int) Cache::get($cacheKey, 0), $dbQuotaUsed);

        $isUnlimited = $user->hasActiveSubscription();
        $trialLimit = (int) \App\Models\SiteSetting::get('pricing_trial_limit', 50);

        // Fetch recent 20 API requests made by this client
        $recentLogs = \App\Models\GatewayLog::where('user_id', $user->id)
            ->latest()
            ->limit(20)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'endpoint' => $log->endpoint,
                    'method' => $log->method,
                    'service' => $log->service,
                    'status_code' => $log->status_code,
                    'response_time_ms' => $log->response_time_ms,
                    'created_at' => $log->created_at->format('H:i:s • d M Y'),
                    'created_at_human' => $log->created_at->diffForHumans(),
                ];
            });

        $paymentProofs = $user->paymentProofs()->latest()->get()->map(function ($p) {
            return [
                'id' => $p->id,
                'plan_name' => $p->plan_name,
                'amount' => $p->amount,
                'proof_url' => $p->proof_url,
                'original_filename' => $p->original_filename,
                'bank_source' => $p->bank_source,
                'notes' => $p->notes,
                'status' => $p->status,
                'admin_notes' => $p->admin_notes,
                'created_at' => $p->created_at->format('d M Y H:i'),
                'verified_at' => $p->verified_at?->format('d M Y H:i'),
            ];
        });

        return Inertia::render('Client/Dashboard', [
            'client' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'is_unlimited' => $isUnlimited,
                'subscription_ends_at' => $user->subscription_ends_at?->format('d M Y H:i'),
                'quota_limit' => $isUnlimited ? null : $trialLimit,
                'quota_used' => $quotaUsed,
                'quota_remaining' => $isUnlimited ? null : max(0, $trialLimit - $quotaUsed),
                'api_key' => $apiKey,
                'created_at' => $user->created_at->format('d M Y'),
            ],
            'endpoints' => [
                'base_url' => url('/api/v1'),
                'bpjs_peserta' => url('/api/v1/bpjs/peserta'),
                'bpjs_sep' => url('/api/v1/bpjs/sep'),
                'antrean_poli' => url('/api/v1/bpjs/referensi/poli'),
            ],
            'recentLogs' => $recentLogs,
            'postmanUrl' => route('downloads.postman'),
            'whatsappContact' => (string) \App\Models\SiteSetting::get('whatsapp_contact', config('app.whatsapp_contact')),
            'telegramContact' => (string) \App\Models\SiteSetting::get('telegram_contact', 'FauziRahman95'),
            'settings' => [
                'pricing_monthly_price' => (int) \App\Models\SiteSetting::get('pricing_monthly_price', 450000),
                'pricing_monthly_promo_code' => (string) \App\Models\SiteSetting::get('pricing_monthly_promo_code', 'SIMRS450'),
                'pricing_monthly_promo_active' => (bool) \App\Models\SiteSetting::get('pricing_monthly_promo_active', true),
                'whatsapp_contact' => (string) \App\Models\SiteSetting::get('whatsapp_contact', '6281234567890'),
                'telegram_contact' => (string) \App\Models\SiteSetting::get('telegram_contact', 'FauziRahman95'),
            ],
            'paymentProofs' => $paymentProofs,
        ]);
    }

    public function regenerateToken(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Revoke old tokens
        $user->tokens()->delete();

        // Generate fresh new Sanctum token
        $newToken = $user->createToken('default-key')->plainTextToken;

        return redirect()->route('client.dashboard')
            ->with('new_api_key', $newToken)
            ->with('success', 'API Key baru berhasil dibuat. Salin dan simpan segera.');
    }
}
