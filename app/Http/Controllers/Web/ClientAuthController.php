<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class ClientAuthController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'whatsappContact' => (string) \App\Models\SiteSetting::get('whatsapp_contact', config('app.whatsapp_contact')),
            'trialLimit' => (int) \App\Models\SiteSetting::get('pricing_trial_limit', 50),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:6'],
        ], [
            'name.required' => 'Nama faskes atau developer wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email ini sudah terdaftar.',
            'password.min' => 'Password minimal 6 karakter.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
        ]);

        // Assign role client or demo
        $role = Role::firstOrCreate(['name' => 'client']);
        $user->assignRole($role);

        // Auto-generate their default API Key using Laravel Sanctum
        $token = $user->createToken('default-key')->plainTextToken;
        session()->flash('new_api_key', $token);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('client.dashboard')->with('success', 'Akun berhasil dibuat! API Key Anda siap digunakan.');
    }
}
