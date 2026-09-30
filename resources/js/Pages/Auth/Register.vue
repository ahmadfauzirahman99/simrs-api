<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    whatsappContact: {
        type: String,
        default: '6281234567890',
    },
    trialLimit: {
        type: Number,
        default: 50,
    },
});

const showPassword = ref(false);

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
});

const submit = () => {
    form.post('/register');
};
</script>

<template>
    <Head title="Pendaftaran Klien SIMRS Gateway - Dapatkan API Key" />

    <div class="relative min-h-screen flex flex-col justify-center items-center bg-slate-50 px-4 py-10 text-slate-900 selection:bg-teal-600 selection:text-white">
        <!-- Subtle Pattern Background -->
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(#cbd5e1_1px,transparent_1px)] [background-size:20px_20px] opacity-60" />

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
                <Link href="/" class="flex items-center gap-2.5 rounded-lg border border-slate-200/90 bg-white px-3.5 py-1.5 shadow-xs hover:border-slate-300 transition">
                    <div class="flex h-6 w-6 items-center justify-center rounded bg-teal-700 text-white font-bold text-xs shadow-2xs">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <span class="font-bold text-sm text-slate-900 tracking-tight">SIMRS Gateway</span>
                    <span class="rounded bg-teal-50 px-1.5 py-0.5 font-mono text-[10px] font-bold text-teal-800 border border-teal-200">v3.0</span>
                </Link>

                <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl pt-1">
                    Daftar Akun Klien & Developer
                </h1>
                <p class="text-xs text-slate-600 max-w-xs leading-relaxed">
                    Dapatkan API Key instan untuk integrasi bridging BPJS (kuota uji coba {{ trialLimit }} request / hari).
                </p>
            </div>

            <!-- Card Box -->
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xl shadow-slate-200/60 ring-1 ring-slate-900/5">
                <div class="p-6 sm:p-7">
                    <form @submit.prevent="submit" class="space-y-4">
                        <!-- Nama Faskes / Developer -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-slate-700">Nama Rumah Sakit / Klinik / Pengembang</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="Contoh: RSU Permata Medika / PT Solusi SIMRS"
                                class="w-full rounded-lg border border-slate-300 bg-slate-50/50 px-3.5 py-2 text-xs text-slate-900 placeholder:text-slate-400 focus:border-teal-600 focus:bg-white focus:outline-none transition shadow-2xs"
                            >
                            <p v-if="form.errors.name" class="text-[11px] font-medium text-rose-600">{{ form.errors.name }}</p>
                        </div>

                        <!-- Email -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-slate-700">Alamat Email Resmi</label>
                            <input
                                v-model="form.email"
                                type="email"
                                required
                                placeholder="it.simrs@rumahsakit.com"
                                class="w-full rounded-lg border border-slate-300 bg-slate-50/50 px-3.5 py-2 text-xs text-slate-900 placeholder:text-slate-400 focus:border-teal-600 focus:bg-white focus:outline-none transition shadow-2xs"
                            >
                            <p v-if="form.errors.email" class="text-[11px] font-medium text-rose-600">{{ form.errors.email }}</p>
                        </div>

                        <!-- WhatsApp Phone -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-semibold text-slate-700">Nomor WhatsApp Aktif</label>
                                <span class="text-[10px] text-slate-400 font-medium">Untuk info aktivasi</span>
                            </div>
                            <input
                                v-model="form.phone"
                                type="text"
                                placeholder="081234567890"
                                class="w-full rounded-lg border border-slate-300 bg-slate-50/50 px-3.5 py-2 text-xs text-slate-900 placeholder:text-slate-400 focus:border-teal-600 focus:bg-white focus:outline-none transition shadow-2xs"
                            >
                            <p v-if="form.errors.phone" class="text-[11px] font-medium text-rose-600">{{ form.errors.phone }}</p>
                        </div>

                        <!-- Password -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-slate-700">Kata Sandi (Minimal 6 Karakter)</label>
                            <div class="relative rounded-lg border border-slate-300 bg-slate-50/50 focus-within:border-teal-600 focus-within:bg-white transition shadow-2xs">
                                <input
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    placeholder="••••••••"
                                    class="w-full rounded-lg bg-transparent px-3.5 py-2 pr-10 text-xs text-slate-900 placeholder:text-slate-400 outline-none"
                                >
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 transition"
                                    @click="showPassword = !showPassword"
                                >
                                    <svg v-if="!showPassword" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                </button>
                            </div>
                            <p v-if="form.errors.password" class="text-[11px] font-medium text-rose-600">{{ form.errors.password }}</p>
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-teal-700 px-4 py-2.5 text-xs font-bold text-white shadow-xs transition hover:bg-teal-800 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-70 mt-2"
                            :disabled="form.processing"
                        >
                            <svg v-if="form.processing" class="h-4 w-4 animate-spin text-teal-100" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>{{ form.processing ? 'Membuat Akun & API Key...' : 'Daftar & Buat API Key' }}</span>
                        </button>
                    </form>

                    <div class="mt-4 pt-4 border-t border-slate-100 text-center text-xs text-slate-600">
                        Sudah punya akun?
                        <Link href="/login" class="font-bold text-teal-700 hover:text-teal-800 hover:underline ml-1">
                            Masuk di sini
                        </Link>
                    </div>
                </div>

                <!-- Footer Info -->
                <div class="border-t border-slate-100 bg-slate-50/70 p-3.5 text-center text-[11px] text-slate-500">
                    Akun baru otomatis mendapatkan <strong>{{ trialLimit }} request uji coba gratis per hari</strong>.
                </div>
            </div>
        </div>
    </div>
</template>
