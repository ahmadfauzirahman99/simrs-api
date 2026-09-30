<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user()
                    ? [
                        'id' => $request->user()->id,
                        'name' => $request->user()->name,
                        'email' => $request->user()->email,
                        'is_superadmin' => $request->user()->hasRole('superadmin'),
                        'subscription_active' => $request->user()->hasActiveSubscription(),
                        'is_demo' => $request->user()->hasRole('demo'),
                        'subscription_ends_at' => $request->user()->subscription_ends_at?->format('d M Y'),
                    ]
                    : null,
            ],
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
            ],
            'site_settings' => fn() => \App\Models\SiteSetting::allFormatted(),
        ]);
    }
}
