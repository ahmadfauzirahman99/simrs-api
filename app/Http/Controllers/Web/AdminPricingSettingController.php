<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminPricingSettingController extends Controller
{
    public function index(): Response
    {
        abort_unless(auth()->user()?->hasRole('superadmin'), 403, 'Akses khusus Superadmin.');

        return Inertia::render('Admin/Settings/Pricing', [
            'settings' => SiteSetting::allFormatted(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->hasRole('superadmin'), 403, 'Akses khusus Superadmin.');

        $validated = $request->validate([
            // Monthly SaaS Plan
            'pricing_monthly_title' => 'required|string|max:100',
            'pricing_monthly_tag' => 'required|string|max:100',
            'pricing_monthly_desc' => 'required|string|max:255',
            'pricing_monthly_price' => 'required|integer|min:0',
            'pricing_monthly_strike_price' => 'nullable|integer|min:0',
            'pricing_monthly_promo_active' => 'boolean',
            'pricing_monthly_promo_badge' => 'nullable|string|max:100',
            'pricing_monthly_promo_code' => 'nullable|string|max:50',
            'pricing_monthly_period' => 'required|string|max:50',
            'pricing_monthly_features' => 'array',
            'pricing_monthly_features.*' => 'string',
            'pricing_monthly_cta_text' => 'required|string|max:100',

            // Trial Plan
            'pricing_trial_title' => 'required|string|max:100',
            'pricing_trial_tag' => 'required|string|max:100',
            'pricing_trial_desc' => 'required|string|max:255',
            'pricing_trial_price' => 'required|integer|min:0',
            'pricing_trial_limit' => 'required|integer|min:1',
            'pricing_trial_features' => 'array',
            'pricing_trial_features.*' => 'string',
            'pricing_trial_cta_text' => 'required|string|max:100',

            // Single Hospital License
            'pricing_hospital_title' => 'required|string|max:100',
            'pricing_hospital_tag' => 'required|string|max:100',
            'pricing_hospital_desc' => 'required|string|max:255',
            'pricing_hospital_price' => 'required|integer|min:0',
            'pricing_hospital_strike_price' => 'nullable|integer|min:0',
            'pricing_hospital_promo_active' => 'boolean',
            'pricing_hospital_promo_badge' => 'nullable|string|max:100',
            'pricing_hospital_period' => 'required|string|max:50',
            'pricing_hospital_features' => 'array',
            'pricing_hospital_features.*' => 'string',
            'pricing_hospital_cta_text' => 'required|string|max:100',

            // Global Announcement Promo Banner
            'promo_banner_active' => 'boolean',
            'promo_banner_text' => 'required|string|max:500',
            'promo_banner_link' => 'nullable|string|max:255',

            // Contact
            'whatsapp_contact' => 'required|string|max:50',
            'telegram_contact' => 'nullable|string|max:50',
            'support_email' => 'required|email|max:100',

            // Rekening & QRIS Pembayaran
            'payment_bank_name' => 'nullable|string|max:50',
            'payment_account_number' => 'nullable|string|max:50',
            'payment_account_holder' => 'nullable|string|max:100',
            'payment_bank_name_2' => 'nullable|string|max:50',
            'payment_account_number_2' => 'nullable|string|max:50',
            'payment_account_holder_2' => 'nullable|string|max:100',
            'payment_qris_active' => 'boolean',
            'payment_qris_image' => 'nullable|string|max:255',
        ]);

        foreach ($validated as $key => $value) {
            $group = match (true) {
                str_starts_with($key, 'pricing_monthly') => 'pricing_monthly',
                str_starts_with($key, 'pricing_trial') => 'pricing_trial',
                str_starts_with($key, 'pricing_hospital') => 'pricing_hospital',
                str_starts_with($key, 'promo_') => 'promo',
                default => 'general',
            };

            SiteSetting::set($key, $value, null, $group);
        }

        // Sync demo account name to match updated trial limit
        $trialLimit = (int) $validated['pricing_trial_limit'];
        \App\Models\User::where('email', 'demo@simrs.local')->update([
            'name' => "Pengguna Uji Coba (Demo {$trialLimit} Req/Hari)",
        ]);

        return redirect()->back()->with('success', 'Pengaturan paket harga dan promo berhasil disimpan!');
    }
}
