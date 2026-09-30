<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('subscription:extend {email} {--months=1}', function (string $email) {
    $user = \App\Models\User::where('email', $email)->first();
    if (!$user) {
        $this->error("User dengan email '{$email}' tidak ditemukan.");
        return 1;
    }

    $months = (int) $this->option('months') ?: 1;
    $base = ($user->subscription_ends_at && $user->subscription_ends_at->isFuture())
        ? $user->subscription_ends_at
        : now();

    $newExpiry = $base->copy()->addMonths($months);
    $user->update(['subscription_ends_at' => $newExpiry]);

    $this->info("✓ Masa aktif {$user->email} diperpanjang {$months} bulan s/d: {$newExpiry->format('Y-m-d H:i:s')}");
    return 0;
})->purpose('Perpanjang masa aktif akses bulanan pengguna');

Artisan::command('subscription:create {email} {--name=} {--password=password} {--months=1}', function (string $email) {
    $name = $this->option('name') ?: explode('@', $email)[0];
    $password = $this->option('password') ?: 'password';
    $months = (int) $this->option('months') ?: 1;

    $user = \App\Models\User::firstOrCreate(
        ['email' => $email],
        ['name' => $name, 'password' => bcrypt($password)]
    );

    $base = ($user->subscription_ends_at && $user->subscription_ends_at->isFuture())
        ? $user->subscription_ends_at
        : now();

    $newExpiry = $base->copy()->addMonths($months);
    $user->update(['subscription_ends_at' => $newExpiry]);

    $this->info("✓ Akun {$user->email} berhasil diaktifkan ({$months} bulan s/d {$newExpiry->format('Y-m-d H:i:s')}). Password: {$password}");
    return 0;
})->purpose('Buat akun baru atau aktifkan akses langganan bulanan');

