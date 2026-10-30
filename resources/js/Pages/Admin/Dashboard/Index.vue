<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    metrics: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    topEndpoints: {
        type: Array,
        default: () => [],
    },
    hourlyTrend: {
        type: Array,
        default: () => [],
    },
    recentLogs: {
        type: Array,
        default: () => [],
    },
    clients: {
        type: Array,
        default: () => [],
    },
    trialLimit: {
        type: Number,
        default: 50,
    },
});

// Filter state
const selectedClient = ref(props.filters?.client_id || '');
const selectedService = ref(props.filters?.service || 'all');
const selectedStatus = ref(props.filters?.status || 'all');
const searchQuery = ref(props.filters?.search || '');

// Inspection Modal state
const inspectingLog = ref(null);
const copiedField = ref('');

const applyFilters = () => {
    router.get('/admin/dashboard', {
        client_id: selectedClient.value || undefined,
        service: selectedService.value !== 'all' ? selectedService.value : undefined,
        status: selectedStatus.value !== 'all' ? selectedStatus.value : undefined,
        search: searchQuery.value ? searchQuery.value : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    selectedClient.value = '';
    selectedService.value = 'all';
    selectedStatus.value = 'all';
    searchQuery.value = '';
    router.get('/admin/dashboard');
};

const filterByClient = (clientId) => {
    selectedClient.value = clientId;
    applyFilters();
};

const copyToClipboard = (text, key) => {
    navigator.clipboard.writeText(typeof text === 'object' ? JSON.stringify(text, null, 2) : text);
    copiedField.value = key;
    setTimeout(() => {
        copiedField.value = '';
    }, 2000);
};

const maxHourlyCount = computed(() => {
    const counts = props.hourlyTrend.map(h => h.count);
    return Math.max(1, ...counts);
});

const currentSelectedClientObj = computed(() => {
    if (!props.filters?.client_id) return null;
    return props.clients.find(c => c.id === props.filters.client_id) || null;
});

const getMethodColor = (method) => {
    switch (method?.toUpperCase()) {
        case 'GET': return 'bg-sky-50 text-sky-700 border-sky-200';
        case 'POST': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'PUT': return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'DELETE': return 'bg-rose-50 text-rose-700 border-rose-200';
        default: return 'bg-slate-50 text-slate-700 border-slate-200';
    }
};

const getStatusBadge = (code) => {
    if (code >= 200 && code < 300) {
        return { label: `${code} OK`, bg: 'bg-emerald-50 text-emerald-700 border-emerald-200' };
    }
    if (code === 401 || code === 403) {
        return { label: `${code} Auth`, bg: 'bg-purple-50 text-purple-700 border-purple-200' };
    }
    if (code === 429) {
        return { label: `${code} Limit`, bg: 'bg-amber-50 text-amber-700 border-amber-200' };
    }
    if (code >= 400 && code < 500) {
        return { label: `${code} Client Err`, bg: 'bg-orange-50 text-orange-700 border-orange-200' };
    }
    return { label: `${code} Server Err`, bg: 'bg-rose-50 text-rose-700 border-rose-200' };
};
</script>

<template>
    <Head title="Dashboard Gateway & Telemetri API" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white rounded-2xl border border-slate-200 p-6 shadow-xs">
                <div>
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-800 text-white font-black shadow-xs">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold tracking-tight text-slate-900">Dashboard Gateway & Telemetri API</h1>
                            <p class="text-xs text-slate-500 font-medium">Monitoring real-time lalu lintas panggilan BPJS, SATUSEHAT, kuota faskes, dan log performa gateway.</p>
                        </div>
                    </div>
                </div>

                <!-- Action Toolbar: Postman & BPJS Tester -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Postman Collection 1-Click Download Button -->
                    <a
                        href="/downloads/postman-collection"
                        download="simrs-gateway-bpjs-postman-collection.json"
                        class="inline-flex items-center gap-2 rounded-xl border border-amber-300 bg-amber-50/80 px-4 py-2.5 text-xs font-bold text-amber-900 hover:bg-amber-100 transition shadow-2xs group"
                        title="Unduh file Postman Collection JSON lengkap v2.1.0"
                    >
                        <div class="flex h-5 w-5 items-center justify-center rounded bg-amber-500 text-white font-bold text-[10px] shadow-2xs">
                            P
                        </div>
                        <span>Postman Collection</span>
                        <span class="rounded bg-amber-200/90 text-amber-900 text-[10px] px-1.5 py-0.5 font-mono font-bold">v2.1.0</span>
                        <svg class="h-3.5 w-3.5 text-amber-700 group-hover:translate-y-0.5 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                    </a>

                    <!-- Tester Studio Link -->
                    <Link
                        href="/admin/bpjs-tester"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold text-white hover:bg-slate-800 transition shadow-xs"
                    >
                        <svg class="h-3.5 w-3.5 text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Uji Endpoint BPJS</span>
                    </Link>

                    <!-- Client Management Link -->
                    <Link
                        href="/admin/clients"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition shadow-2xs"
                    >
                        <span>Kelola Klien & Lisensi</span>
                    </Link>
                </div>
            </div>

            <!-- Active Client Filter Alert Banner (if filtered) -->
            <div v-if="currentSelectedClientObj" class="rounded-xl border border-teal-200 bg-teal-50 p-4 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-700 text-white font-bold text-xs">
                        🏥
                    </span>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-teal-950">Menampilkan Filter Khusus Faskes: {{ currentSelectedClientObj.name }}</span>
                            <span class="rounded bg-teal-200/80 px-2 py-0.5 text-[10px] font-bold text-teal-900 uppercase">
                                {{ currentSelectedClientObj.subscription_status === 'active' ? 'Pro Aktif (Unlimited)' : 'Trial Limit' }}
                            </span>
                        </div>
                        <p class="text-[11px] text-teal-800">
                            Email: {{ currentSelectedClientObj.email }} • Pemakaian Hari Ini: <strong>{{ currentSelectedClientObj.today_usage }}</strong> panggilan
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="resetFilters"
                    class="rounded-lg bg-white px-3 py-1.5 text-xs font-bold text-slate-700 border border-slate-200 hover:bg-slate-50 transition self-start sm:self-auto"
                >
                    ✕ Tampilkan Semua Klien
                </button>
            </div>

            <!-- Primary Metrics Overview (4 Key Metrics) -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Card 1: Total Calls Today -->
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Panggilan Hari Ini</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-teal-50 text-teal-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3">
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-extrabold text-slate-900 font-mono">{{ metrics.total_requests_today.toLocaleString('id-ID') }}</span>
                            <span class="text-xs text-slate-500 font-medium">request</span>
                        </div>
                        <p class="mt-1 text-[11px] text-slate-500">Mulai 00:00 WIB hari ini</p>
                    </div>
                </div>

                <!-- Card 2: Success Rate -->
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Tingkat Keberhasilan</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3">
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-extrabold text-emerald-700 font-mono">{{ metrics.success_rate }}%</span>
                            <span class="rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-bold text-emerald-800">
                                {{ metrics.success_requests_today }} Sukses
                            </span>
                        </div>
                        <p class="mt-1 text-[11px] text-slate-500">
                            <span class="text-rose-600 font-semibold">{{ metrics.error_requests_today }} Gagal / Ditolak</span> dalam 24 jam
                        </p>
                    </div>
                </div>

                <!-- Card 3: Average Latency -->
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Rata-rata Respon</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-sky-50 text-sky-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3">
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-extrabold text-slate-900 font-mono">{{ metrics.avg_latency_ms }}</span>
                            <span class="text-xs text-slate-500 font-medium">ms</span>
                        </div>
                        <div class="mt-1 flex items-center gap-1.5 text-[11px]">
                            <span class="inline-block h-2 w-2 rounded-full" :class="metrics.avg_latency_ms < 500 ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                            <span class="text-slate-600">{{ metrics.avg_latency_ms < 500 ? 'Sangat Cepat (< 500ms)' : 'Wajar' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Active Clients Today -->
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Faskes Aktif Hari Ini</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-purple-50 text-purple-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3">
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-extrabold text-slate-900 font-mono">{{ metrics.active_clients_today }}</span>
                            <span class="text-xs text-slate-500 font-medium">/ {{ metrics.total_clients }} Klien</span>
                        </div>
                        <p class="mt-1 text-[11px] text-slate-500">Faskes yang mengirim request hari ini</p>
                    </div>
                </div>
            </div>

            <!-- Two Column Section: Hourly Distribution & Top Endpoints -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
                <!-- Hourly Traffic Bar Chart (7 Cols) -->
                <div class="lg:col-span-7 rounded-2xl border border-slate-200 bg-white p-6 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                            <div>
                                <h2 class="text-sm font-bold text-slate-900">Distribusi Trafik 24 Jam</h2>
                                <p class="text-xs text-slate-500">Volume request per jam pada hari ini (00:00 - 23:00 WIB)</p>
                            </div>
                            <span class="rounded-md bg-slate-100 px-2.5 py-1 text-[11px] font-mono font-semibold text-slate-700">
                                {{ metrics.total_requests_today }} req hari ini
                            </span>
                        </div>

                        <!-- Bar Graph -->
                        <div class="pt-6">
                            <div class="flex items-end justify-between gap-1.5 h-44 px-1">
                                <div
                                    v-for="item in hourlyTrend"
                                    :key="item.hour"
                                    class="group relative flex flex-1 flex-col items-center h-full justify-end"
                                >
                                    <!-- Tooltip -->
                                    <div class="absolute bottom-full mb-2 hidden group-hover:flex flex-col items-center z-20 pointer-events-none">
                                        <div class="rounded-lg bg-slate-900 px-2 py-1 text-[10px] font-mono text-white whitespace-nowrap shadow-lg">
                                            {{ item.hour }}: <strong>{{ item.count }} req</strong>
                                        </div>
                                        <div class="w-2 h-2 bg-slate-900 rotate-45 -mt-1"></div>
                                    </div>

                                    <!-- Bar Column -->
                                    <div
                                        class="w-full rounded-t transition-all duration-300"
                                        :class="item.count > 0 ? 'bg-teal-700 hover:bg-teal-600' : 'bg-slate-100 hover:bg-slate-200'"
                                        :style="{ height: `${Math.max(4, (item.count / maxHourlyCount) * 100)}%` }"
                                    ></div>
                                    <span class="text-[9px] text-slate-400 font-mono mt-1 hidden sm:block">
                                        {{ item.hour.slice(0, 2) }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-[10px] text-slate-400 font-mono px-1">
                                <span>00:00 WIB</span>
                                <span>12:00 Siang</span>
                                <span>23:00 Malam</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 text-xs text-slate-500 flex items-center justify-between">
                        <span>Puncak beban server dimonitor otomatis.</span>
                        <span class="font-semibold text-teal-800">Kapasitas: 120 req/menit</span>
                    </div>
                </div>

                <!-- Top Endpoints Ranked (5 Cols) -->
                <div class="lg:col-span-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                            <div>
                                <h2 class="text-sm font-bold text-slate-900">Endpoint Paling Sering Digunakan</h2>
                                <p class="text-xs text-slate-500">Peringkat rute API berdasarkan volume pemanggilan</p>
                            </div>
                            <span class="rounded bg-teal-50 px-2 py-0.5 text-xs font-bold text-teal-800">Top 8</span>
                        </div>

                        <!-- Endpoints List -->
                        <div class="divide-y divide-slate-100 pt-2">
                            <div
                                v-for="(item, idx) in topEndpoints"
                                :key="item.endpoint + item.method"
                                class="py-2.5 flex items-center justify-between gap-3 text-xs"
                            >
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[10px] font-bold text-slate-600">
                                        {{ idx + 1 }}
                                    </span>
                                    <span class="rounded px-1.5 py-0.5 font-mono text-[10px] font-bold border" :class="getMethodColor(item.method)">
                                        {{ item.method }}
                                    </span>
                                    <div class="truncate">
                                        <div class="font-mono font-medium text-slate-900 truncate" :title="item.endpoint">
                                            {{ item.endpoint }}
                                        </div>
                                        <div class="text-[10px] text-slate-400">
                                            {{ item.service || 'BPJS Gateway' }} • {{ item.avg_latency || 0 }}ms
                                        </div>
                                    </div>
                                </div>
                                <div class="shrink-0 text-right">
                                    <span class="font-mono font-bold text-slate-900">{{ item.count }}</span>
                                    <span class="text-[10px] text-slate-500 block">req</span>
                                </div>
                            </div>

                            <div v-if="topEndpoints.length === 0" class="py-12 text-center text-xs text-slate-400">
                                Belum ada panggilan endpoint hari ini.
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 text-xs flex items-center justify-between">
                        <span class="text-slate-500">Total endpoint tersedia:</span>
                        <span class="font-bold text-slate-800">209 Bridging Endpoints</span>
                    </div>
                </div>
            </div>

            <!-- Client Quota & Activity Summary Strip -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Pemakaian Kuota Per Faskes / Klien Hari Ini</h2>
                        <p class="text-xs text-slate-500">Klik salah satu klien untuk memfilter log audit khusus faskes tersebut.</p>
                    </div>
                    <Link
                        href="/admin/clients"
                        class="text-xs font-bold text-teal-700 hover:text-teal-900 hover:underline"
                    >
                        Kelola Lisensi & Limit Klien →
                    </Link>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3.5">
                    <div
                        v-for="c in clients"
                        :key="c.id"
                        @click="filterByClient(c.id)"
                        class="rounded-xl border p-4 cursor-pointer transition hover:shadow-sm"
                        :class="selectedClient === c.id ? 'border-teal-600 bg-teal-50/50 ring-2 ring-teal-500/20' : 'border-slate-200 bg-slate-50/60 hover:bg-white'"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-900 truncate pr-2">{{ c.name }}</span>
                            <span
                                class="rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider"
                                :class="c.subscription_status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                            >
                                {{ c.subscription_status === 'active' ? 'Pro' : 'Trial' }}
                            </span>
                        </div>
                        <div class="mt-2 flex items-baseline justify-between">
                            <span class="text-xs text-slate-500">Pemakaian:</span>
                            <span class="font-mono text-xs font-bold text-slate-900">
                                {{ c.today_usage }} <span class="text-[10px] text-slate-400 font-normal">/ {{ c.subscription_status === 'active' ? '∞' : c.quota_limit }} req</span>
                            </span>
                        </div>
                        <!-- Mini progress bar -->
                        <div v-if="c.subscription_status !== 'active'" class="w-full bg-slate-200 rounded-full h-1.5 mt-1.5 overflow-hidden">
                            <div
                                class="bg-amber-500 h-1.5 rounded-full"
                                :style="{ width: `${Math.min(100, (c.today_usage / (c.quota_limit || 1)) * 100)}%` }"
                            ></div>
                        </div>
                        <div v-else class="text-[10px] text-emerald-700 font-medium mt-1">
                            ✓ Akses Unlimited Bebas Limit
                        </div>
                    </div>
                </div>
            </div>

            <!-- Audit Trail: Live Gateway Logs Table -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span>📡 Audit Trail & Log Panggilan Gateway</span>
                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-mono font-bold text-slate-700">
                                {{ recentLogs.length }} request terakhir
                            </span>
                        </h2>
                        <p class="text-xs text-slate-500">Catatan detail setiap request yang masuk ke gateway, status respon, durasi latensi, dan isi payload.</p>
                    </div>

                    <!-- Filter Controls -->
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Client Dropdown Filter -->
                        <select
                            v-model="selectedClient"
                            @change="applyFilters"
                            class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 focus:border-teal-600 focus:bg-white focus:outline-hidden"
                        >
                            <option value="">Semua Klien (Faskes)</option>
                            <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>

                        <!-- Service Filter -->
                        <select
                            v-model="selectedService"
                            @change="applyFilters"
                            class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 focus:border-teal-600 focus:bg-white focus:outline-hidden"
                        >
                            <option value="all">Semua Layanan</option>
                            <option value="BPJS VClaim">BPJS VClaim</option>
                            <option value="BPJS Antrean">BPJS Antrean</option>
                            <option value="SATUSEHAT FHIR">SATUSEHAT FHIR</option>
                            <option value="Simulator Studio">Simulator Studio</option>
                        </select>

                        <!-- Status Filter -->
                        <select
                            v-model="selectedStatus"
                            @change="applyFilters"
                            class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 focus:border-teal-600 focus:bg-white focus:outline-hidden"
                        >
                            <option value="all">Semua Status</option>
                            <option value="success">Sukses (2xx)</option>
                            <option value="error">Error / Ditolak (4xx / 5xx)</option>
                        </select>

                        <!-- Search Box -->
                        <div class="relative">
                            <input
                                v-model="searchQuery"
                                @keyup.enter="applyFilters"
                                type="text"
                                placeholder="Cari endpoint / IP..."
                                class="rounded-xl border border-slate-200 bg-slate-50 pl-8 pr-3 py-2 text-xs text-slate-800 placeholder-slate-400 focus:border-teal-600 focus:bg-white focus:outline-hidden w-44 sm:w-56"
                            />
                            <svg class="h-3.5 w-3.5 text-slate-400 absolute left-2.5 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        <!-- Reset Button -->
                        <button
                            type="button"
                            @click="resetFilters"
                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 transition"
                            title="Reset Filter"
                        >
                            Reset
                        </button>
                    </div>
                </div>

                <!-- Logs Table -->
                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 font-semibold">
                            <tr>
                                <th class="p-3 whitespace-nowrap">Waktu (WIB)</th>
                                <th class="p-3">Faskes / Klien</th>
                                <th class="p-3">Method & Endpoint</th>
                                <th class="p-3">Layanan</th>
                                <th class="p-3">Status</th>
                                <th class="p-3">Latensi</th>
                                <th class="p-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="log in recentLogs" :key="log.id" class="hover:bg-slate-50/70 transition">
                                <!-- Waktu -->
                                <td class="p-3 whitespace-nowrap font-mono text-slate-600 text-[11px]">
                                    <div class="font-bold text-slate-900 flex items-center gap-1.5">
                                        <span>{{ log.created_at_time }}</span>
                                        <span class="text-[9px] font-semibold text-emerald-700 bg-emerald-50 px-1 py-0.2 rounded border border-emerald-200">WIB</span>
                                    </div>
                                    <div class="text-[10px] text-slate-400">{{ log.created_at_human }}</div>
                                </td>

                                <!-- Faskes / Klien -->
                                <td class="p-3">
                                    <div class="font-bold text-slate-900">{{ log.client_name }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ log.ip_address || '127.0.0.1' }}</div>
                                </td>

                                <!-- Endpoint -->
                                <td class="p-3">
                                    <div class="flex items-center gap-1.5">
                                        <span class="rounded px-1.5 py-0.5 font-mono text-[10px] font-bold border" :class="getMethodColor(log.method)">
                                            {{ log.method }}
                                        </span>
                                        <span class="font-mono text-slate-800 text-[11px] font-medium truncate max-w-xs md:max-w-md" :title="log.endpoint">
                                            {{ log.endpoint }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Layanan -->
                                <td class="p-3 whitespace-nowrap">
                                    <span class="rounded bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-700">
                                        {{ log.service || 'BPJS Gateway' }}
                                    </span>
                                    <span v-if="log.is_mock" class="ml-1 rounded bg-amber-50 border border-amber-200 px-1.5 py-0.2 text-[9px] text-amber-700 font-bold">
                                        MOCK
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="p-3 whitespace-nowrap">
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-bold font-mono" :class="getStatusBadge(log.status_code).bg">
                                        {{ getStatusBadge(log.status_code).label }}
                                    </span>
                                </td>

                                <!-- Latensi -->
                                <td class="p-3 whitespace-nowrap font-mono text-[11px]">
                                    <span :class="log.response_time_ms < 500 ? 'text-emerald-700 font-semibold' : 'text-amber-700 font-semibold'">
                                        {{ log.response_time_ms }} ms
                                    </span>
                                </td>

                                <!-- Aksi -->
                                <td class="p-3 text-right whitespace-nowrap">
                                    <button
                                        type="button"
                                        class="rounded-lg bg-slate-100 hover:bg-slate-200 px-2.5 py-1 text-[11px] font-bold text-slate-700 transition"
                                        @click="inspectingLog = log"
                                    >
                                        🔍 Detail Payload
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="recentLogs.length === 0">
                                <td colspan="7" class="p-12 text-center text-xs text-slate-400">
                                    Tidak ada log panggilan yang cocok dengan kriteria filter saat ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MODAL: Inspection Payload Modal -->
        <div v-if="inspectingLog" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs overflow-y-auto">
            <div class="relative w-full max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl space-y-4 my-8">
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <span class="rounded px-2 py-0.5 font-mono text-xs font-bold border" :class="getMethodColor(inspectingLog.method)">
                            {{ inspectingLog.method }}
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 font-mono">{{ inspectingLog.endpoint }}</h3>
                            <p class="text-[11px] text-slate-500">
                                {{ inspectingLog.client_name }} • {{ inspectingLog.created_at }} • {{ inspectingLog.response_time_ms }} ms
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="text-slate-400 hover:text-slate-600 font-bold text-xl leading-none"
                        @click="inspectingLog = null"
                    >
                        &times;
                    </button>
                </div>

                <!-- Meta Info Strip -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs bg-slate-50 p-3 rounded-xl border border-slate-200">
                    <div>
                        <span class="text-[10px] text-slate-400 block uppercase">Status Code</span>
                        <span class="font-mono font-bold" :class="inspectingLog.status_code < 400 ? 'text-emerald-700' : 'text-rose-600'">
                            {{ inspectingLog.status_code }}
                        </span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 block uppercase">IP Address</span>
                        <span class="font-mono text-slate-700">{{ inspectingLog.ip_address || '127.0.0.1' }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 block uppercase">Layanan</span>
                        <span class="font-semibold text-slate-800">{{ inspectingLog.service }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 block uppercase">Mode Eksekusi</span>
                        <span class="font-semibold" :class="inspectingLog.is_mock ? 'text-amber-700' : 'text-teal-700'">
                            {{ inspectingLog.is_mock ? 'Mocking Studio' : 'Live Gateway' }}
                        </span>
                    </div>
                </div>

                <!-- Request Payload -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-800">Request Body / Payload:</span>
                        <button
                            v-if="inspectingLog.request_payload"
                            type="button"
                            class="text-[11px] font-bold text-teal-700 hover:underline"
                            @click="copyToClipboard(inspectingLog.request_payload, 'payload')"
                        >
                            {{ copiedField === 'payload' ? '✓ Tersalin' : 'Salin Payload' }}
                        </button>
                    </div>
                    <div class="rounded-xl border border-slate-800 bg-slate-950 p-3 text-xs font-mono text-emerald-300 max-h-48 overflow-y-auto">
                        <pre v-if="inspectingLog.request_payload">{{ JSON.stringify(inspectingLog.request_payload, null, 2) }}</pre>
                        <span v-else class="text-slate-500 italic">Tidak ada payload request (HTTP GET)</span>
                    </div>
                </div>

                <!-- Response Summary -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-800">Ringkasan Respon Gateway:</span>
                        <button
                            v-if="inspectingLog.response_summary"
                            type="button"
                            class="text-[11px] font-bold text-teal-700 hover:underline"
                            @click="copyToClipboard(inspectingLog.response_summary, 'response')"
                        >
                            {{ copiedField === 'response' ? '✓ Tersalin' : 'Salin Respon' }}
                        </button>
                    </div>
                    <div class="rounded-xl border border-slate-800 bg-slate-950 p-3 text-xs font-mono text-cyan-300 max-h-48 overflow-y-auto">
                        <pre v-if="inspectingLog.response_summary">{{ JSON.stringify(inspectingLog.response_summary, null, 2) }}</pre>
                        <span v-else class="text-slate-500 italic">Respon standar terkirim.</span>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex justify-end pt-2 border-t border-slate-100">
                    <button
                        type="button"
                        class="rounded-xl bg-slate-800 hover:bg-slate-900 px-4 py-2 text-xs font-bold text-white transition"
                        @click="inspectingLog = null"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
