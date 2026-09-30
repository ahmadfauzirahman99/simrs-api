<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\GatewayLog;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class AdminClientController extends Controller
{
    private function authorizeSuperadmin(Request $request): void
    {
        abort_unless($request->user() && $request->user()->hasRole('superadmin'), 403, 'Akses khusus Superadmin.');
    }

    public function index(Request $request): Response
    {
        $this->authorizeSuperadmin($request);

        $search = $request->input('search');
        $status = $request->input('status', 'all');

        $query = User::with('tokens')
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'superadmin');
            });

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $now = now();
        if ($status === 'active') {
            $query->whereNotNull('subscription_ends_at')->where('subscription_ends_at', '>', $now);
        } elseif ($status === 'trial') {
            $query->whereNull('subscription_ends_at');
        } elseif ($status === 'expired') {
            $query->whereNotNull('subscription_ends_at')->where('subscription_ends_at', '<=', $now);
        }

        $clients = $query->latest()->get();

        $today = now()->format('Y-m-d');
        $startOfToday = now()->startOfDay();
        $trialLimit = (int) SiteSetting::get('pricing_trial_limit', 50);

        // Fetch usage from persistent gateway_logs table
        $dbUsagePerUser = GatewayLog::where('created_at', '>=', $startOfToday)
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->selectRaw('user_id, count(*) as total')
            ->pluck('total', 'user_id');

        $totalGatewayRequestsToday = GatewayLog::where('created_at', '>=', $startOfToday)->count();

        $clientItems = $clients->map(function (User $client) use ($today, $dbUsagePerUser, $trialLimit) {
            $cachedUsage = (int) Cache::get("client_req_{$client->id}_{$today}", 0);
            $dbUsage = (int) ($dbUsagePerUser[$client->id] ?? 0);
            $todayUsage = max($cachedUsage, $dbUsage);

            $isSubscribed = $client->subscription_ends_at && $client->subscription_ends_at->isFuture();
            $isExpired = $client->subscription_ends_at && $client->subscription_ends_at->isPast();

            $daysLeft = $isSubscribed ? now()->diffInDays($client->subscription_ends_at, false) : 0;

            $displayName = $client->name;
            if ($client->hasRole('demo') || $client->email === 'demo@simrs.local') {
                $displayName = "Pengguna Uji Coba (Demo {$trialLimit} Req/Hari)";
            }

            return [
                'id' => $client->id,
                'name' => $displayName,
                'email' => $client->email,
                'phone' => $client->phone,
                'subscription_ends_at' => $client->subscription_ends_at?->format('d M Y H:i'),
                'subscription_status' => $isSubscribed ? 'active' : ($isExpired ? 'expired' : 'trial'),
                'days_left' => $daysLeft,
                'tokens_count' => $client->tokens->count(),
                'latest_token_name' => $client->tokens->first()?->name,
                'latest_token_created_at' => $client->tokens->first()?->created_at?->format('d M Y H:i'),
                'today_usage' => $todayUsage,
                'quota_limit' => $trialLimit,
                'created_at' => $client->created_at->format('d M Y'),
            ];
        });

        // Compute summary metrics
        $allClients = User::whereDoesntHave('roles', fn($q) => $q->where('name', 'superadmin'))->get();
        $activeSubscribersCount = $allClients->filter(fn(User $u) => $u->subscription_ends_at && $u->subscription_ends_at->isFuture())->count();
        $trialCount = $allClients->filter(fn(User $u) => $u->subscription_ends_at === null)->count();
        $expiredCount = $allClients->filter(fn(User $u) => $u->subscription_ends_at && $u->subscription_ends_at->isPast())->count();

        $paymentProofs = \App\Models\PaymentProof::with('user')->latest()->get()->map(function ($p) {
            return [
                'id' => $p->id,
                'user_id' => $p->user_id,
                'client_name' => $p->user?->name ?? 'Klien Dihapus',
                'client_email' => $p->user?->email,
                'plan_name' => $p->plan_name,
                'amount' => $p->amount,
                'proof_url' => $p->proof_url,
                'original_filename' => $p->original_filename,
                'bank_source' => $p->bank_source,
                'notes' => $p->notes,
                'status' => $p->status,
                'admin_notes' => $p->admin_notes,
                'uploaded_by_role' => $p->uploaded_by_role,
                'created_at' => $p->created_at->format('d M Y H:i'),
                'verified_at' => $p->verified_at?->format('d M Y H:i'),
            ];
        });

        return Inertia::render('Admin/Clients/Index', [
            'clients' => $clientItems,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'paymentProofs' => $paymentProofs,
            'telegramContact' => (string) SiteSetting::get('telegram_contact', 'FauziRahman95'),
            'trialLimit' => $trialLimit,
            'metrics' => [
                'total_clients' => $allClients->count(),
                'active_subscribers' => $activeSubscribersCount,
                'trial_clients' => $trialCount,
                'expired_clients' => $expiredCount,
                'pending_proofs_count' => $paymentProofs->where('status', 'pending')->count(),
                'estimated_mrr' => $activeSubscribersCount * (int) SiteSetting::get('pricing_monthly_price', 450000),
                'total_gateway_requests_today' => $totalGatewayRequestsToday,
            ],
        ]);
    }

    public function extendSubscription(Request $request, User $user): RedirectResponse
    {
        $this->authorizeSuperadmin($request);

        $days = (int) $request->input('days', 30);

        if ($user->subscription_ends_at && $user->subscription_ends_at->isFuture()) {
            $user->subscription_ends_at = $user->subscription_ends_at->addDays($days);
        } else {
            $user->subscription_ends_at = now()->addDays($days);
        }

        $user->save();

        return back()->with('success', "Masa langganan {$user->name} berhasil diperpanjang {$days} hari (s/d {$user->subscription_ends_at->format('d M Y')}).");
    }

    public function setTrial(Request $request, User $user): RedirectResponse
    {
        $this->authorizeSuperadmin($request);

        $user->subscription_ends_at = null;
        $user->save();

        $trialLimit = (int) SiteSetting::get('pricing_trial_limit', 50);

        return back()->with('success', "Status {$user->name} berhasil dikembalikan ke status Trial ({$trialLimit} request/hari).");
    }

    public function revokeTokens(Request $request, User $user): RedirectResponse
    {
        $this->authorizeSuperadmin($request);

        $user->tokens()->delete();

        return back()->with('success', "Seluruh API Key {$user->name} berhasil dicabut.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorizeSuperadmin($request);

        abort_if($user->id === $request->user()->id, 400, 'Tidak dapat menghapus akun Anda sendiri.');
        abort_if($user->hasRole('superadmin'), 403, 'Tidak dapat menghapus akun Superadmin lain.');

        $name = $user->name;
        $user->tokens()->delete();
        $user->delete();

        return back()->with('success', "Akun klien {$name} berhasil dihapus.");
    }
}
