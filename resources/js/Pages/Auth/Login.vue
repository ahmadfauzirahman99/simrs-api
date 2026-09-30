<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    defaultEmail: {
        type: String,
        default: 'admin@simrs.local',
    },
    defaultPassword: {
        type: String,
        default: 'password',
    },
    trialLimit: {
        type: Number,
        default: 50,
    },
});

const showPassword = ref(false);
const autoFillNotice = ref('');

const form = useForm({
    email: props.defaultEmail,
    password: props.defaultPassword,
});

const fillDefault = () => {
    form.email = props.defaultEmail || 'admin@simrs.local';
    form.password = props.defaultPassword || 'password';
    autoFillNotice.value = 'Kredensial default berhasil diterapkan';
    window.setTimeout(() => {
        autoFillNotice.value = '';
    }, 2500);
};

const submit = () => {
    form.post('/login');
};

const loginDemo = () => {
    router.post('/demo-login');
};
</script>

<template>
    <Head title="Masuk ke SIMRS Gateway & BPJS Studio" />

    <div class="relative min-h-screen flex flex-col justify-center items-center bg-slate-50 px-4 py-10 text-slate-900 selection:bg-teal-600 selection:text-white">
        <!-- Subtle Pattern Background for Depth -->
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(#cbd5e1_1px,transparent_1px)] [background-size:20px_20px] opacity-60" />

        <!-- Main Auth Container -->
        <div class="relative w-full max-w-md space-y-6">
            <!-- Back to Home Link -->
            <div class="flex items-center justify-between">
                <Link
                    href="/"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-teal-800 transition group"
                >
                    <svg class="h-3.5 w-3.5 group-hover:-translate-x-0.5 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Kembali ke Beranda</span>
                </Link>
            </div>

            <!-- Header Brand -->
            <div class="flex flex-col items-center text-center space-y-2">
                <div class="flex items-center gap-2.5 rounded-lg border border-slate-200/90 bg-white px-3.5 py-1.5 shadow-xs">
                    <div class="flex h-6 w-6 items-center justify-center rounded bg-teal-700 text-white font-bold text-xs shadow-2xs">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <span class="font-bold text-sm text-slate-900 tracking-tight">SIMRS Gateway</span>
                    <span class="rounded bg-teal-50 px-1.5 py-0.5 font-mono text-[10px] font-bold text-teal-800 border border-teal-200">v3.0</span>
                    <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                </div>

                <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl pt-1">
                    Masuk ke Gateway Console
                </h1>
                <p class="text-xs text-slate-500 max-w-xs leading-relaxed">
                    Akses BPJS Integration Studio dan simulator bridging rumah sakit.
                </p>
            </div>

            <!-- Login Card (Clean Light Aesthetic) -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <!-- Top Brand Accent Line -->
                <div class="h-1 w-full bg-gradient-to-r from-teal-600 via-emerald-500 to-teal-700"></div>

                <div class="p-6 space-y-4">
                    <!-- General / Validation Error Alert -->
                    <div
                        v-if="form.errors.email || form.errors.password"
                        class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-xs text-rose-800 flex items-start gap-2.5"
                    >
                        <svg class="h-4 w-4 shrink-0 text-rose-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div class="space-y-0.5">
                            <p class="font-bold text-rose-900">Autentikasi Gagal</p>
                            <p class="text-[11px] text-rose-700 leading-relaxed">
                                {{ form.errors.email || form.errors.password || 'Email atau password yang dimasukkan salah, atau akun bukan Super Admin.' }}
                            </p>
                        </div>
                    </div>

                    <!-- Login Form -->
                    <form class="space-y-4" @submit.prevent="submit">
                        <!-- Email Input -->
                        <div class="space-y-1">
                            <label class="block text-xs font-semibold text-slate-700">
                                Email Pengguna
                            </label>
                            <div class="relative rounded-lg border border-slate-300 bg-white focus-within:border-teal-600 focus-within:ring-1 focus-within:ring-teal-600 transition shadow-2xs">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                    </svg>
                                </div>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    required
                                    placeholder="nama@simrs.local"
                                    class="w-full rounded-lg bg-transparent py-2 pl-9 pr-3 text-xs text-slate-900 placeholder:text-slate-400 outline-none"
                                >
                            </div>
                        </div>

                        <!-- Password Input -->
                        <div class="space-y-1">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-semibold text-slate-700">
                                    Kata Sandi
                                </label>
                                <button
                                    type="button"
                                    tabindex="-1"
                                    class="text-[11px] font-medium text-teal-700 hover:text-teal-900 transition"
                                    @click="showPassword = !showPassword"
                                >
                                    {{ showPassword ? 'Sembunyikan' : 'Tampilkan' }}
                                </button>
                            </div>
                            <div class="relative rounded-lg border border-slate-300 bg-white focus-within:border-teal-600 focus-within:ring-1 focus-within:ring-teal-600 transition shadow-2xs">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <input
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    placeholder="••••••••"
                                    class="w-full rounded-lg bg-transparent py-2 pl-9 pr-10 text-xs text-slate-900 placeholder:text-slate-400 outline-none"
                                >
                                <button
                                    type="button"
                                    tabindex="-1"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 transition"
                                    @click="showPassword = !showPassword"
                                >
                                    <!-- Eye / Eye Off -->
                                    <svg v-if="!showPassword" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Quick 1-Click Fill Helper for Development -->
                        <div class="rounded-lg border border-slate-200 bg-slate-50/80 p-2.5 flex items-center justify-between text-[11px]">
                            <div class="flex items-center gap-1.5 text-slate-600">
                                <svg class="h-3.5 w-3.5 text-teal-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Akun Default: <strong class="text-slate-800">Super Admin</strong></span>
                            </div>
                            <button
                                type="button"
                                class="font-bold text-teal-700 hover:text-teal-800 hover:underline transition"
                                @click="fillDefault"
                            >
                                Isi Otomatis
                            </button>
                        </div>
                        <p v-if="autoFillNotice" class="text-center font-medium text-[11px] text-emerald-700 transition">
                            ✓ {{ autoFillNotice }}
                        </p>

                        <!-- Submit Button with Processing State -->
                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-teal-700 px-4 py-2.5 text-xs font-bold text-white shadow-xs transition hover:bg-teal-800 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-70"
                            :disabled="form.processing"
                        >
                            <svg v-if="form.processing" class="h-4 w-4 animate-spin text-teal-100" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <svg v-else class="h-4 w-4 text-teal-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                            <span>{{ form.processing ? 'Memverifikasi Akses...' : 'Masuk ke Gateway Console' }}</span>
                        </button>

                        <!-- Separator / Or -->
                        <div class="relative flex items-center justify-center pt-1">
                            <div class="border-t border-slate-200 w-full"></div>
                            <span class="bg-white px-2.5 text-[10px] uppercase font-bold text-slate-400 tracking-wider">atau</span>
                            <div class="border-t border-slate-200 w-full"></div>
                        </div>

                        <!-- 1-Click Demo / Trial Button -->
                        <button
                            type="button"
                            @click="loginDemo"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-teal-300 bg-teal-50 px-4 py-2.5 text-xs font-bold text-teal-800 transition hover:bg-teal-100 shadow-2xs"
                        >
                            <svg class="h-4 w-4 text-teal-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Coba Demo Gratis (Limit {{ trialLimit }} Request/Hari)</span>
                        </button>

                        <div class="pt-2 text-center text-xs text-slate-600">
                            Belum punya akun?
                            <Link href="/register" class="font-bold text-teal-700 hover:text-teal-800 hover:underline ml-1">
                                Daftar Klien Baru & Dapatkan API Key →
                            </Link>
                        </div>
                    </form>
                </div>

                <!-- Card Footer: System Telemetry Specs (Light Theme) -->
                <div class="border-t border-slate-100 bg-slate-50/70 p-3.5 space-y-2 text-[10px]">
                    <div class="flex items-center justify-between text-slate-600">
                        <span class="font-semibold text-slate-700">Cakupan Modul BPJS API</span>
                        <span class="font-mono text-teal-800 font-bold">209 Endpoint</span>
                    </div>

                    <!-- Module Chips -->
                    <div class="flex flex-wrap gap-1">
                        <span class="rounded bg-white px-1.5 py-0.5 text-slate-700 border border-slate-200 shadow-2xs">VClaim (110)</span>
                        <span class="rounded bg-white px-1.5 py-0.5 text-slate-700 border border-slate-200 shadow-2xs">Antrean RS (18)</span>
                        <span class="rounded bg-white px-1.5 py-0.5 text-slate-700 border border-slate-200 shadow-2xs">Apotek (18)</span>
                        <span class="rounded bg-white px-1.5 py-0.5 text-slate-700 border border-slate-200 shadow-2xs">PCare (51)</span>
                        <span class="rounded bg-white px-1.5 py-0.5 text-slate-700 border border-slate-200 shadow-2xs">Aplicares (5)</span>
                        <span class="rounded bg-white px-1.5 py-0.5 text-slate-700 border border-slate-200 shadow-2xs">i-Care (2)</span>
                        <span class="rounded bg-white px-1.5 py-0.5 text-slate-700 border border-slate-200 shadow-2xs">Antrean FKTP (5)</span>
                    </div>
                </div>
            </div>

            <!-- Bottom System Notice -->
            <div class="flex flex-col items-center justify-center text-center space-y-1 text-[11px] text-slate-500">
                <div class="flex items-center gap-1.5 text-slate-600">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                    <span class="font-medium">Enkripsi AES-256-CBC • HMAC-SHA256 Signature Generator</span>
                </div>
                <p class="text-[10px] text-slate-400">
                    Koneksi terenkripsi. Hak akses dibatasi untuk Super Administrator SIMRS.
                </p>
            </div>
        </div>
    </div>
</template>
