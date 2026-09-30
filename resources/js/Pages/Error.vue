<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: Number,
        default: 404,
    },
});

const errorDetails = computed(() => {
    switch (props.status) {
        case 429:
            return {
                badge: 'Rate Limit Terlampaui',
                title: 'Terlalu Banyak Permintaan (429)',
                description: 'Sistem proteksi mendeteksi frekuensi pengiriman request melebihi batas wajar. Silakan tunggu 1 menit sebelum mencoba kembali.',
                iconColor: 'amber',
                tip: 'Fitur Rate Limiting aktif otomatis untuk mencegah brute-force dan menjaga kestabilan server gateway.',
            };
        case 403:
            return {
                badge: 'Akses Terbatas',
                title: 'Hak Akses Ditolak (403)',
                description: 'Anda tidak memiliki hak akses atau peran (role) yang diperlukan untuk membuka halaman ini.',
                iconColor: 'rose',
                tip: 'Pastikan Anda telah masuk menggunakan akun dengan peran Super Administrator SIMRS.',
            };
        case 500:
        case 503:
            return {
                badge: 'Kendala Sistem',
                title: 'Gangguan Server Gateway (500)',
                description: 'Terjadi kesalahan internal saat memproses request bridging atau server sedang dalam pemeliharaan.',
                iconColor: 'rose',
                tip: 'Silakan hubungi administrator IT rumah sakit atau cek koneksi intranet server.',
            };
        case 404:
        default:
            return {
                badge: 'Tidak Ditemukan',
                title: 'Halaman Tidak Ditemukan (404)',
                description: 'Halaman, route, atau endpoint yang Anda cari tidak tersedia di SIMRS Gateway.',
                iconColor: 'sky',
                tip: 'Periksa kembali penulisan URL atau kembali ke halaman utama.',
            };
    }
});

const reloadPage = () => {
    if (typeof window !== 'undefined') {
        window.location.reload();
    }
};
</script>

<template>
    <Head :title="`${props.status} - ${errorDetails.title}`" />

    <div class="min-h-screen flex flex-col justify-center items-center bg-slate-50 px-4 py-12 text-slate-900 selection:bg-teal-600 selection:text-white relative">
        <!-- Subtle Pattern Background -->
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(#cbd5e1_1px,transparent_1px)] [background-size:20px_20px] opacity-60" />

        <div class="relative w-full max-w-lg space-y-6 text-center">
            <!-- Brand Badge -->
            <div class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1 shadow-2xs">
                <span class="h-2 w-2 rounded-full" :class="props.status === 429 ? 'bg-amber-500' : 'bg-rose-500'"></span>
                <span class="text-xs font-bold text-slate-800">SIMRS Gateway Security</span>
                <span class="rounded bg-slate-100 px-1.5 py-0.2 font-mono text-[10px] text-slate-600 border border-slate-200">v3.0</span>
            </div>

            <!-- Error Card -->
            <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm text-left space-y-6">
                <!-- Status Code Display -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <span class="font-mono text-4xl font-extrabold text-slate-900 tracking-tight">
                        {{ props.status }}
                    </span>
                    <span
                        class="rounded-full px-3 py-1 text-xs font-bold"
                        :class="props.status === 429
                            ? 'bg-amber-50 text-amber-800 border border-amber-200'
                            : props.status === 404
                            ? 'bg-sky-50 text-sky-800 border border-sky-200'
                            : 'bg-rose-50 text-rose-800 border border-rose-200'"
                    >
                        {{ errorDetails.badge }}
                    </span>
                </div>

                <!-- Text Content -->
                <div class="space-y-2">
                    <h1 class="text-lg font-bold text-slate-900 sm:text-xl">
                        {{ errorDetails.title }}
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        {{ errorDetails.description }}
                    </p>
                </div>

                <!-- Security / Technical Tip -->
                <div class="rounded-lg bg-slate-50 border border-slate-200 p-3 text-xs text-slate-600 flex items-start gap-2.5">
                    <svg class="h-4 w-4 shrink-0 text-teal-700 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-[11px] leading-relaxed">
                        {{ errorDetails.tip }}
                    </p>
                </div>

                <!-- Recovery Actions -->
                <div class="flex flex-col sm:flex-row items-center gap-2 pt-2">
                    <Link
                        href="/"
                        class="w-full sm:w-auto flex-1 inline-flex items-center justify-center gap-1.5 rounded-lg bg-teal-700 px-4 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-teal-800 transition"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Kembali ke Beranda</span>
                    </Link>

                    <button
                        type="button"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition"
                        @click="reloadPage"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Coba Muat Ulang</span>
                    </button>
                </div>
            </div>

            <!-- Footer Meta -->
            <p class="text-[10px] text-slate-400">
                SIMRS Gateway • Proteksi Keamanan & Rate Limiting Aktif
            </p>
        </div>
    </div>
</template>
