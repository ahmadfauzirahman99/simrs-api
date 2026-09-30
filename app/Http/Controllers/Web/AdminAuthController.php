<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminAuthController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'defaultEmail' => 'admin@simrs.local',
            'defaultPassword' => 'password',
            'trialLimit' => (int) \App\Models\SiteSetting::get('pricing_trial_limit', 50),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials, false)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password tidak valid.',
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();

        $isExpired = $user && $user->subscription_ends_at && $user->subscription_ends_at->isPast();

        if ($isExpired && !$user->hasRole('superadmin')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Masa aktif langganan bulanan Anda telah berakhir. Silakan hubungi kami via WhatsApp untuk perpanjangan.',
            ]);
        }

        $isAllowed = $user && (
            $user->hasRole('superadmin')
            || $user->hasActiveSubscription()
            || $user->hasRole('demo')
            || $user->hasRole('client')
        );

        if (!$isAllowed) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Akun Anda tidak memiliki lisensi atau masa aktif langganan yang valid.',
            ]);
        }

        if ($user->hasRole('superadmin')) {
            return redirect()->route('admin.clients');
        }

        if ($user->hasRole('client')) {
            return redirect()->route('client.dashboard');
        }

        return redirect()->route('admin.bpjs-tester');
    }

    public function demoLogin(Request $request): RedirectResponse
    {
        $demoUser = \App\Models\User::firstOrCreate(
            ['email' => 'demo@simrs.local'],
            [
                'name' => 'Pengguna Uji Coba (Demo)',
                'password' => bcrypt('password'),
            ]
        );

        if (!$demoUser->hasRole('demo')) {
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'demo']);
            $demoUser->assignRole('demo');
        }

        Auth::login($demoUser);
        $request->session()->regenerate();

        return redirect()->route('admin.bpjs-tester');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
