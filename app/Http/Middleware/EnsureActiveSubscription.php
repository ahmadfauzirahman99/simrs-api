<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.standalone')) {
            return $next($request);
        }

        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // If paid subscription expired, block access
        if ($user->subscription_ends_at && $user->subscription_ends_at->isPast() && !$user->hasRole('superadmin')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Masa aktif langganan bulanan Anda telah berakhir.',
                ], 403);
            }

            return redirect()->route('login')->withErrors([
                'email' => 'Masa aktif langganan bulanan Anda telah berakhir. Silakan perpanjang untuk melanjutkan akses.',
            ]);
        }

        // Allowed users: superadmin, subscribers, demo role, or registered clients (trial/pro)
        $isAllowed = $user->hasRole('superadmin')
            || $user->hasActiveSubscription()
            || $user->hasRole('demo')
            || $user->hasRole('client');

        if (!$isAllowed) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Akun Anda tidak memiliki lisensi atau masa aktif langganan yang valid.',
                ], 403);
            }

            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda tidak memiliki lisensi atau masa aktif langganan yang valid.',
            ]);
        }

        return $next($request);
    }
}
