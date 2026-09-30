<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
    ];

    public static function defaults(): array
    {
        return [
            // Paket Bulanan (SaaS / Akses Semaunya)
            'pricing_monthly_title' => 'Lisensi Bulanan (SaaS)',
            'pricing_monthly_tag' => 'Akses Semaunya',
            'pricing_monthly_desc' => 'Akses tanpa batas 50 req/hari, uji coba bebas semua modul sepuasnya.',
            'pricing_monthly_price' => 450000,
            'pricing_monthly_strike_price' => 750000,
            'pricing_monthly_promo_active' => true,
            'pricing_monthly_promo_badge' => 'Promo Diskon 40%',
            'pricing_monthly_promo_code' => 'SIMRS450',
            'pricing_monthly_period' => '/ bulan',
            'pricing_monthly_features' => [
                '✓ Unlimited Request (Akses Semaunya)',
                '✓ Semua 209 Endpoint Simulator & Proxy',
                '✓ Akun Personal Aktif 30 Hari',
                '✓ Perpanjangan Fleksibel Setiap Bulan',
                '✓ Garansi Support Teknis Integrasi',
            ],
            'pricing_monthly_cta_text' => 'Langganan Rp 450.000 / Bulan',

            // Paket Coba Gratis (Trial)
            'pricing_trial_title' => 'Coba Gratis (Trial)',
            'pricing_trial_tag' => 'Uji Coba Terbatas',
            'pricing_trial_desc' => 'Untuk developer yang ingin mencoba simulator sebelum berlangganan.',
            'pricing_trial_price' => 0,
            'pricing_trial_limit' => 50,
            'pricing_trial_features' => [
                '⚠️ Limit 50 Request / Hari',
                '✓ Akses Simulator UI (209 Endpoint)',
                '✓ Preview Data Mock Realistis',
                '✓ Tanpa Kartu Kredit / Tanpa Bayar',
            ],
            'pricing_trial_cta_text' => 'Daftar Uji Coba (50 Req / Hari)',

            // Paket Single Hospital (Source Code)
            'pricing_hospital_title' => 'Single Hospital',
            'pricing_hospital_tag' => 'Beli Putus Source Code',
            'pricing_hospital_desc' => 'Full source code untuk diinstalasi di server lokal intranet 1 Rumah Sakit.',
            'pricing_hospital_price' => 4500000,
            'pricing_hospital_strike_price' => 6500000,
            'pricing_hospital_promo_active' => true,
            'pricing_hospital_promo_badge' => 'Hemat Rp 2 Juta',
            'pricing_hospital_period' => '(Sekali Bayar)',
            'pricing_hospital_features' => [
                '✓ Full Source Code (Laravel + Vue)',
                '✓ Deploy Intranet (Kepatuhan PMK 24)',
                '✓ Tanpa Biaya Bulanan / Transaksi',
                '✓ Panduan Setup & Garansi 30 Hari',
            ],
            'pricing_hospital_cta_text' => 'Beli Source Code RS',

            // Promo Banner Global
            'promo_banner_active' => true,
            'promo_banner_text' => '🎉 PROMO SPESIAL BULAN INI: Lisensi Bulanan Akses Semaunya hanya Rp 450.000/bln (Hemat Rp 300.000)! Gunakan kode promo SIMRS450.',
            'promo_banner_link' => '#paket-lisensi',

            // Kontak
            'whatsapp_contact' => '6281234567890',
            'telegram_contact' => 'FauziRahman95',
            'support_email' => 'support@simrsgateway.id',

            // Rekening Pembayaran & QRIS
            'payment_bank_name' => 'Bank BCA',
            'payment_account_number' => '8831-2940-11',
            'payment_account_holder' => 'Ahmad Fauzi Rahman',
            'payment_bank_name_2' => 'Bank Mandiri',
            'payment_account_number_2' => '',
            'payment_account_holder_2' => '',
            'payment_qris_active' => true,
            'payment_qris_image' => '/images/qris.jpeg',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $defaults = self::defaults();
        $fallback = $default ?? ($defaults[$key] ?? null);

        try {
            $cached = Cache::remember("site_setting_{$key}", 300, function () use ($key) {
                return self::where('key', $key)->first();
            });

            if (! $cached) {
                return $fallback;
            }

            return self::castValue($cached->value, $cached->type);
        } catch (\Throwable) {
            return $fallback;
        }
    }

    public static function set(string $key, mixed $value, ?string $type = null, string $group = 'general'): self
    {
        if (is_null($type)) {
            $type = match (true) {
                is_bool($value) => 'boolean',
                is_int($value) => 'integer',
                is_array($value) => 'json',
                default => 'string',
            };
        }

        $serialized = match ($type) {
            'boolean' => $value ? '1' : '0',
            'json', 'array' => is_string($value) ? $value : json_encode($value),
            default => (string) $value,
        };

        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $serialized,
                'type' => $type,
                'group' => $group,
            ]
        );

        Cache::forget("site_setting_{$key}");
        Cache::forget('site_settings_all_formatted');

        return $setting;
    }

    public static function allFormatted(): array
    {
        $defaults = self::defaults();

        try {
            return Cache::remember('site_settings_all_formatted', 300, function () use ($defaults) {
                $records = self::all()->keyBy('key');

                $result = [];
                foreach ($defaults as $k => $defVal) {
                    if ($records->has($k)) {
                        $rec = $records->get($k);
                        $result[$k] = self::castValue($rec->value, $rec->type);
                    } else {
                        $result[$k] = $defVal;
                    }
                }

                // Also include any extra custom keys in database
                foreach ($records as $k => $rec) {
                    if (! array_key_exists($k, $result)) {
                        $result[$k] = self::castValue($rec->value, $rec->type);
                    }
                }

                // Automatically keep texts and feature items synchronized with dynamic pricing_trial_limit
                $trialLimit = (int) ($result['pricing_trial_limit'] ?? 50);
                if (isset($result['pricing_trial_features']) && is_array($result['pricing_trial_features'])) {
                    $result['pricing_trial_features'] = array_map(function ($feat) use ($trialLimit) {
                        return preg_replace('/Limit \d+ Request/i', "Limit {$trialLimit} Request", (string) $feat);
                    }, $result['pricing_trial_features']);
                }
                if (isset($result['pricing_monthly_desc'])) {
                    $result['pricing_monthly_desc'] = preg_replace(
                        '/Akses tanpa batas \d+ req\/hari/i',
                        "Akses tanpa batas {$trialLimit} req/hari",
                        (string) $result['pricing_monthly_desc']
                    );
                }
                if (isset($result['pricing_trial_cta_text'])) {
                    $result['pricing_trial_cta_text'] = preg_replace(
                        '/Daftar Uji Coba \(\d+ Req \/ Hari\)/i',
                        "Daftar Uji Coba ({$trialLimit} Req / Hari)",
                        (string) $result['pricing_trial_cta_text']
                    );
                }

                return $result;
            });
        } catch (\Throwable) {
            return $defaults;
        }
    }

    public static function castValue(?string $value, string $type): mixed
    {
        if (is_null($value)) {
            return null;
        }

        return match ($type) {
            'integer', 'int' => (int) $value,
            'float', 'double' => (float) $value,
            'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json', 'array' => json_decode($value, true) ?? [],
            default => (string) $value,
        };
    }
}
