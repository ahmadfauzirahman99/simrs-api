<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    currentVersion: {
        type: String,
        required: true,
    },
    releaseDate: {
        type: String,
        required: true,
    },
    releaseName: {
        type: String,
        required: true,
    },
    releases: {
        type: Array,
        required: true,
    },
    repoUrl: {
        type: String,
        default: 'https://github.com/ahmadfauzirahman99/simrs-api',
    },
});

const isChecking = ref(false);
const updateInfo = ref(null);
const checkError = ref(null);

const checkUpdate = async () => {
    isChecking.value = true;
    checkError.value = null;
    try {
        const res = await fetch('/admin/version/check');
        const data = await res.json();
        updateInfo.value = data;
    } catch (e) {
        checkError.value = 'Gagal menghubungi server pembaruan GitHub. Pastikan server memiliki koneksi internet.';
    } finally {
        isChecking.value = false;
    }
};

const getBadgeClass = (color) => {
    switch (color) {
        case 'emerald':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'blue':
            return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'purple':
            return 'bg-purple-50 text-purple-700 border-purple-200';
        case 'amber':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        default:
            return 'bg-slate-100 text-slate-700 border-slate-200';
    }
};
</script>

<template>
    <Head title="Pembaruan & Riwayat Versi - SIMRS Gateway" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-900 flex items-center gap-2.5">
                        <span>Pembaruan & Riwayat Versi</span>
                        <span class="rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200 px-2 py-0.5 text-xs font-mono font-bold">
                            {{ currentVersion }}
                        </span>
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Pusat informasi rilis, status sinkronisasi repositori open-source, dan catatan perubahan SIMRS Gateway.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        @click="checkUpdate"
                        :disabled="isChecking"
                        class="inline-flex items-center gap-2 rounded-xl bg-teal-800 hover:bg-teal-900 text-white px-4 py-2 text-xs font-medium shadow-sm transition disabled:opacity-60 cursor-pointer"
                    >
                        <svg
                            class="w-4 h-4"
                            :class="{ 'animate-spin': isChecking }"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>{{ isChecking ? 'Memeriksa GitHub...' : 'Periksa Update Terbaru' }}</span>
                    </button>

                    <a
                        :href="repoUrl"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 px-3.5 py-2 text-xs font-medium shadow-xs transition"
                    >
                        <svg class="w-4 h-4 text-slate-700" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" />
                        </svg>
                        <span>GitHub Repo</span>
                    </a>
                </div>
            </div>

            <!-- Update Status Result Card -->
            <div v-if="updateInfo" class="rounded-2xl border border-teal-200 bg-teal-50/70 p-5 transition">
                <div class="flex items-start gap-3.5">
                    <div class="h-9 w-9 rounded-xl bg-teal-800 text-white flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <h2 class="font-bold text-sm text-teal-950">Status Versi Terkini</h2>
                            <span class="rounded bg-teal-100 text-teal-900 border border-teal-300 text-[10px] px-2 py-0.5 font-bold">Up to Date</span>
                        </div>
                        <p class="text-xs text-teal-800 mt-1 leading-relaxed">
                            {{ updateInfo.message }}
                        </p>
                        <div v-if="updateInfo.latest_commit" class="mt-3 flex flex-wrap items-center gap-3 text-[11px] font-mono text-teal-900 bg-white/80 p-2.5 rounded-lg border border-teal-200/80">
                            <span>Commit: <strong>{{ updateInfo.latest_commit.sha }}</strong></span>
                            <span>•</span>
                            <span class="truncate max-w-md">"{{ updateInfo.latest_commit.message }}"</span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="checkError" class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-800">
                {{ checkError }}
            </div>

            <!-- Current Version Overview Banner -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold uppercase tracking-wider text-teal-700">Versi Terinstal</span>
                            <span class="inline-flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        </div>
                        <h2 class="text-2xl font-black tracking-tight text-slate-900">
                            {{ currentVersion }} <span class="text-slate-400 font-normal text-lg">— {{ releaseName }}</span>
                        </h2>
                        <p class="text-xs text-slate-500">
                            Dirilis pada {{ releaseDate }} • Lisensi Terbuka MIT • Kompatibel Laravel 12 & Vue 3
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <div class="rounded-xl border border-slate-100 bg-slate-50 p-3 text-center min-w-[110px]">
                            <div class="text-[10px] font-semibold text-slate-400 uppercase">Framework</div>
                            <div class="text-sm font-bold text-slate-800 mt-0.5">Laravel 12.x</div>
                        </div>
                        <div class="rounded-xl border border-slate-100 bg-slate-50 p-3 text-center min-w-[110px]">
                            <div class="text-[10px] font-semibold text-slate-400 uppercase">Frontend</div>
                            <div class="text-sm font-bold text-slate-800 mt-0.5">Inertia Vue 3</div>
                        </div>
                        <div class="rounded-xl border border-slate-100 bg-slate-50 p-3 text-center min-w-[110px]">
                            <div class="text-[10px] font-semibold text-slate-400 uppercase">Endpoints</div>
                            <div class="text-sm font-bold text-slate-800 mt-0.5">209 BPJS & SS</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- How to Update Guide -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2 mb-3">
                    <svg class="w-4 h-4 text-teal-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Cara Melakukan Pembaruan (Update Guide)</span>
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4 space-y-2">
                        <div class="font-bold text-slate-900">Metode 1: Git Pull (Direkomendasikan)</div>
                        <p class="text-slate-600 leading-relaxed">
                            Cukup jalankan perintah git pull di server lokal RS untuk mendapatkan seluruh pembaruan bridging BPJS dan SATUSEHAT terbaru:
                        </p>
                        <pre class="bg-slate-900 text-slate-100 p-2.5 rounded-lg font-mono text-[11px] overflow-x-auto">git checkout pmaster
git pull origin pmaster
composer install
npm run build
php artisan migrate</pre>
                    </div>

                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4 space-y-2">
                        <div class="font-bold text-slate-900">Metode 2: Download ZIP Manual</div>
                        <p class="text-slate-600 leading-relaxed">
                            Bagi rumah sakit yang tidak terhubung ke Git, unduh source code terbaru dalam format file ZIP dari GitHub repositori resmi, lalu timpa file di server intranet Anda.
                        </p>
                        <div class="pt-2">
                            <a
                                :href="`${repoUrl}/archive/refs/heads/pmaster.zip`"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-white border border-slate-200 px-3 py-1.5 font-semibold text-slate-700 hover:bg-slate-100 transition"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                <span>Download Source Code (pmaster.zip)</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Release History / Changelog Timeline -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Riwayat Rilis & Catatan Perubahan (Changelog)</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Daftar evolusi fitur dari versi awal hingga rilis stabil terkini.</p>
                    </div>
                </div>

                <div class="space-y-8 relative before:absolute before:inset-0 before:left-4 before:h-full before:w-0.5 before:bg-slate-200">
                    <div
                        v-for="(rel, idx) in releases"
                        :key="rel.version"
                        class="relative flex items-start gap-4 pl-10"
                    >
                        <!-- Timeline Dot -->
                        <div
                            class="absolute left-2.5 top-1 -translate-x-1/2 rounded-full border-2 border-white flex items-center justify-center shadow-xs"
                            :class="idx === 0 ? 'h-4 w-4 bg-emerald-500 ring-4 ring-emerald-100' : 'h-3.5 w-3.5 bg-slate-400'"
                        ></div>

                        <!-- Card Release -->
                        <div class="flex-1 rounded-xl border border-slate-200 bg-white p-5 hover:border-slate-300 transition">
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-sm font-bold text-slate-900">{{ rel.version }}</span>
                                    <span
                                        class="rounded px-2 py-0.5 text-[10px] font-bold border"
                                        :class="getBadgeClass(rel.badge_color)"
                                    >
                                        {{ rel.badge }}
                                    </span>
                                </div>
                                <span class="text-xs text-slate-400 font-medium">{{ rel.date }}</span>
                            </div>

                            <h4 class="text-xs font-bold text-slate-800 mb-3">{{ rel.title }}</h4>

                            <ul class="space-y-1.5 text-xs text-slate-600">
                                <li
                                    v-for="(point, pIdx) in rel.highlights"
                                    :key="pIdx"
                                    class="flex items-start gap-2"
                                >
                                    <span class="text-teal-600 font-bold mt-0.5">•</span>
                                    <span class="leading-relaxed">{{ point }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
