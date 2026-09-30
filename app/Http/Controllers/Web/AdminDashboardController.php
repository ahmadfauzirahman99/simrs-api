<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\GatewayLog;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user() && $request->user()->hasRole('superadmin'), 403, 'Akses khusus Superadmin.');

        $todayStart = now()->startOfDay();
        $selectedClientId = $request->input('client_id');
        $serviceFilter = $request->input('service', 'all');
        $statusFilter = $request->input('status', 'all');
        $search = $request->input('search');

        // Query for Recent Logs
        $logsQuery = GatewayLog::with('user')->latest();

        if ($selectedClientId) {
            $logsQuery->where('user_id', $selectedClientId);
        }

        if ($serviceFilter && $serviceFilter !== 'all') {
            $logsQuery->where('service', $serviceFilter);
        }

        if ($statusFilter === 'success') {
            $logsQuery->whereBetween('status_code', [200, 299]);
        } elseif ($statusFilter === 'error') {
            $logsQuery->where('status_code', '>=', 400);
        }

        if ($search) {
            $logsQuery->where(function ($q) use ($search) {
                $q->where('endpoint', 'like', "%{$search}%")
                    ->orWhere('client_name', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        $logs = $logsQuery->limit(100)->get()->map(function (GatewayLog $log) {
            return [
                'id' => $log->id,
                'user_id' => $log->user_id,
                'client_name' => $log->client_name ?: ($log->user?->name ?? 'Anonim / Publik'),
                'client_email' => $log->client_email ?: $log->user?->email,
                'endpoint' => $log->endpoint,
                'method' => $log->method,
                'service' => $log->service,
                'status_code' => $log->status_code,
                'response_time_ms' => $log->response_time_ms,
                'ip_address' => $log->ip_address,
                'request_payload' => $log->request_payload,
                'response_summary' => $log->response_summary,
                'is_mock' => (bool) $log->is_mock,
                'created_at' => $log->created_at->format('H:i:s • d M Y'),
                'created_at_time' => $log->created_at->format('H:i:s'),
                'created_at_human' => $log->created_at->diffForHumans(),
            ];
        });

        // Summary Statistics (Today)
        $todayLogs = GatewayLog::where('created_at', '>=', $todayStart);
        if ($selectedClientId) {
            $todayLogs->where('user_id', $selectedClientId);
        }

        $totalRequestsToday = (clone $todayLogs)->count();
        $successRequestsToday = (clone $todayLogs)->whereBetween('status_code', [200, 299])->count();
        $errorRequestsToday = (clone $todayLogs)->where('status_code', '>=', 400)->count();
        $avgLatencyMs = (int) round((clone $todayLogs)->avg('response_time_ms') ?: 0);
        $successRate = $totalRequestsToday > 0 ? round(($successRequestsToday / $totalRequestsToday) * 100, 1) : 100.0;

        // Top Endpoints Today
        $topEndpoints = (clone $todayLogs)
            ->selectRaw('endpoint, method, service, count(*) as count, round(avg(response_time_ms)) as avg_latency')
            ->groupBy('endpoint', 'method', 'service')
            ->orderByDesc('count')
            ->limit(8)
            ->get();

        // Hourly Trend Today (00:00 - 23:00) - Database-agnostic (MySQL, SQLite, etc.)
        $hourlyCounts = array_fill(0, 24, 0);
        foreach ((clone $todayLogs)->select('created_at')->get() as $log) {
            if ($log->created_at) {
                $h = (int) $log->created_at->format('H');
                if (isset($hourlyCounts[$h])) {
                    $hourlyCounts[$h]++;
                }
            }
        }

        $hourlyTrend = [];
        for ($h = 0; $h <= 23; $h++) {
            $hourKey = sprintf('%02d', $h);
            $hourlyTrend[] = [
                'hour' => "{$hourKey}:00",
                'count' => $hourlyCounts[$h],
            ];
        }

        // All Registered Clients for Per-Client Selector & Stats
        $trialLimit = (int) SiteSetting::get('pricing_trial_limit', 50);
        $clients = User::whereDoesntHave('roles', fn($q) => $q->where('name', 'superadmin'))
            ->withCount('tokens')
            ->latest()
            ->get()
            ->map(function (User $client) use ($todayStart, $trialLimit) {
                $todayCalls = GatewayLog::where('user_id', $client->id)
                    ->where('created_at', '>=', $todayStart)
                    ->count();

                $isSubscribed = $client->subscription_ends_at && $client->subscription_ends_at->isFuture();

                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'email' => $client->email,
                    'phone' => $client->phone,
                    'subscription_status' => $isSubscribed ? 'active' : 'trial',
                    'today_usage' => $todayCalls,
                    'quota_limit' => $trialLimit,
                    'tokens_count' => $client->tokens_count,
                    'created_at' => $client->created_at->format('d M Y'),
                ];
            });

        return Inertia::render('Admin/Dashboard/Index', [
            'metrics' => [
                'total_requests_today' => $totalRequestsToday,
                'success_requests_today' => $successRequestsToday,
                'error_requests_today' => $errorRequestsToday,
                'success_rate' => $successRate,
                'avg_latency_ms' => $avgLatencyMs,
                'active_clients_today' => GatewayLog::where('created_at', '>=', $todayStart)->distinct('user_id')->count('user_id'),
                'total_clients' => $clients->count(),
            ],
            'filters' => [
                'client_id' => $selectedClientId ? (int) $selectedClientId : null,
                'service' => $serviceFilter,
                'status' => $statusFilter,
                'search' => $search ?: '',
            ],
            'topEndpoints' => $topEndpoints,
            'hourlyTrend' => $hourlyTrend,
            'recentLogs' => $logs,
            'clients' => $clients,
            'trialLimit' => $trialLimit,
        ]);
    }
}
