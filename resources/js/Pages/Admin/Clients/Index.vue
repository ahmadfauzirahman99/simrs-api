<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    clients: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', status: 'all' }),
    },
    paymentProofs: {
        type: Array,
        default: () => [],
    },
    telegramContact: {
        type: String,
        default: 'FauziRahman95',
    },
    trialLimit: {
        type: Number,
        default: 50,
    },
    metrics: {
        type: Object,
        default: () => ({
            total_clients: 0,
            active_subscribers: 0,
            trial_clients: 0,
            expired_clients: 0,
            pending_proofs_count: 0,
            estimated_mrr: 0,
            total_gateway_requests_today: 0,
        }),
    },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);

const adminActiveTab = ref('clients'); // 'clients' | 'proofs'
const search = ref(props.filters.search || '');
const currentStatus = ref(props.filters.status || 'all');
const actionLoading = ref(null);

// Payment Proof Admin Actions & Upload Modal
const showAdminUploadModal = ref(false);
const adminPreviewImage = ref(null);
const adminFileInputRef = ref(null);
const adminUploadError = ref('');
const selectedProofZoom = ref(null);

const adminProofForm = useForm({
    user_id: '',
    plan_name: 'Lisensi Bulanan (SaaS) - Akses Semaunya',
    amount: 450000,
    bank_source: 'Transfer BCA / Mandiri',
    notes: 'Diterima via konfirmasi Telegram/WhatsApp',
    auto_approve: true,
    extend_days: 30,
    proof_image: null,
});

const onAdminFileSelected = (event) => {
    adminUploadError.value = '';
    const file = event.target.files?.[0];
    if (!file) {
        adminProofForm.proof_image = null;
        adminPreviewImage.value = null;
        return;
    }

    // STRICT VALIDATION: ONLY IMAGES ALLOWED!
    const allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    if (!allowedMimes.includes(file.type) && !file.type.startsWith('image/')) {
        adminUploadError.value = 'Hanya file gambar (JPG, JPEG, PNG, WEBP) yang diperbolehkan! Format lain dilarang.';
        event.target.value = '';
        adminProofForm.proof_image = null;
        adminPreviewImage.value = null;
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        adminUploadError.value = 'Ukuran file gambar maksimal 5 MB.';
        event.target.value = '';
        adminProofForm.proof_image = null;
        adminPreviewImage.value = null;
        return;
    }

    adminProofForm.proof_image = file;
    const reader = new FileReader();
    reader.onload = (e) => {
        adminPreviewImage.value = e.target.result;
    };
    reader.readAsDataURL(file);
};

const submitAdminProof = () => {
    if (!adminProofForm.user_id) {
        adminUploadError.value = 'Silakan pilih klien terlebih dahulu.';
        return;
    }
    if (!adminProofForm.proof_image) {
        adminUploadError.value = 'Silakan pilih file gambar bukti transfer.';
        return;
    }

    adminProofForm.post('/admin/payment-proofs', {
        preserveScroll: true,
        onSuccess: () => {
            showAdminUploadModal.value = false;
            adminProofForm.reset('proof_image', 'notes');
            adminPreviewImage.value = null;
            if (adminFileInputRef.value) adminFileInputRef.value.value = '';
        },
    });
};

const approveProof = (proofId, days = 30) => {
    if (confirm(`Setujui pembayaran ini dan perpanjang langganan ${days} hari?`)) {
        router.post(`/admin/payment-proofs/${proofId}/approve`, { days }, { preserveScroll: true });
    }
};

const rejectProof = (proofId) => {
    const note = prompt('Masukkan alasan penolakan bukti transfer:', 'Bukti transfer tidak sesuai / tidak valid');
    if (note !== null) {
        router.post(`/admin/payment-proofs/${proofId}/reject`, { admin_notes: note }, { preserveScroll: true });
    }
};

const deleteProof = (proofId) => {
    if (confirm('Hapus riwayat bukti pembayaran ini?')) {
        router.delete(`/admin/payment-proofs/${proofId}`, { preserveScroll: true });
    }
};

const telegramHandle = computed(() => {
    return (props.telegramContact || page.props.site_settings?.telegram_contact || 'FauziRahman95').replace('@', '');
});

// Modal state
const selectedClient = ref(null);
const customDays = ref(30);

const openManageModal = (client) => {
    selectedClient.value = client;
    customDays.value = 30;
};

const closeManageModal = () => {
    selectedClient.value = null;
};

const applyFilters = () => {
    router.get('/admin/clients', {
        search: search.value,
        status: currentStatus.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const setStatusFilter = (status) => {
    currentStatus.value = status;
    applyFilters();
};

const extendSubscription = (clientId, clientName, days = 30) => {
    actionLoading.value = clientId;
    router.post(`/admin/clients/${clientId}/extend`, { days }, {
        preserveScroll: true,
        onFinish: () => {
            actionLoading.value = null;
            if (selectedClient.value?.id === clientId) {
                closeManageModal();
            }
        },
    });
};

const setTrial = (clientId, clientName) => {
    if (!confirm(`Kembalikan ${clientName} ke status Trial (${currentTrialLimit.value} request/hari)?`)) {
        return;
    }
    actionLoading.value = clientId;
    router.post(`/admin/clients/${clientId}/set-trial`, {}, {
        preserveScroll: true,
        onFinish: () => {
            actionLoading.value = null;
            closeManageModal();
        },
    });
};

const revokeTokens = (clientId, clientName) => {
    if (!confirm(`PERINGATAN: Cabut semua API Key untuk ${clientName}? Sistem SIMRS mereka tidak akan bisa memanggil API sampai token baru dibuat.`)) {
        return;
    }
    actionLoading.value = clientId;
    router.post(`/admin/clients/${clientId}/revoke-tokens`, {}, {
        preserveScroll: true,
        onFinish: () => {
            actionLoading.value = null;
            closeManageModal();
        },
    });
};

const deleteClient = (clientId, clientName) => {
    if (!confirm(`HAPUS AKUN KLIEN: Anda yakin ingin menghapus akun ${clientName}? Tindakan ini permanen.`)) {
        return;
    }
    actionLoading.value = clientId;
    router.delete(`/admin/clients/${clientId}`, {
        preserveScroll: true,
        onFinish: () => {
            actionLoading.value = null;
            closeManageModal();
        },
    });
};

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0);
};

const cleanPhoneForWa = (phone) => {
    if (!phone) return '';
    let cleaned = phone.replace(/[^0-9]/g, '');
    if (cleaned.startsWith('0')) {
        cleaned = '62' + cleaned.slice(1);
    }
    return cleaned;
};

const settings = computed(() => page.props.site_settings ?? {});
const currentTrialLimit = computed(() => props.trialLimit || Number(settings.value.pricing_trial_limit ?? 50));

const formatRibuanSingkat = (val) => {
    const num = Number(val || 0);
    if (num >= 1000000) return `Rp ${(num / 1000000).toLocaleString('id-ID')}jt`;
    if (num >= 1000) return `Rp ${(num / 1000).toLocaleString('id-ID')}k`;
    return `Rp ${num}`;
};
</script>

<template>
    <Head title="Admin Panel - Kelola Klien & Lisensi" />

    <AdminLayout
        title="Admin Panel: Kelola Klien & Lisensi Faskes"
        :subtitle="`Pantau rumah sakit & klinik terdaftar, penggunaan kuota gateway harian, dan aktivasi lisensi berlangganan ${formatRupiah(settings.pricing_monthly_price ?? 450000)} / bulan.`"
    >
        <!-- Flash Alert -->
        <div v-if="flashSuccess" class="mb-5 flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-xs">
            <div class="flex items-center gap-2.5">
                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <span class="font-semibold">{{ flashSuccess }}</span>
            </div>
            <button type="button" @click="page.props.flash.success = null" class="text-emerald-600 hover:text-emerald-900 font-bold text-lg leading-none">&times;</button>
        </div>

        <!-- Metric Summary Cards -->
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <!-- Total Clients -->
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Klien RS</p>
                    <span class="rounded-lg bg-blue-50 p-2 text-blue-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-1.5">
                    <span class="text-2xl font-black text-slate-900">{{ metrics.total_clients }}</span>
                    <span class="text-xs text-slate-500 font-medium">faskes terdaftar</span>
                </div>
            </div>

            <!-- Pro Active -->
            <div class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider">Pro Aktif</p>
                    <span class="rounded-lg bg-emerald-100 p-2 text-emerald-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-1.5">
                    <span class="text-2xl font-black text-emerald-900">{{ metrics.active_subscribers }}</span>
                    <span class="text-xs font-semibold text-emerald-700">Akses Semaunya</span>
                </div>
            </div>

            <!-- Estimasi MRR -->
            <div class="rounded-xl border border-teal-200 bg-white p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Estimasi MRR</p>
                    <span class="rounded-lg bg-teal-50 px-2 py-1 text-xs font-bold text-teal-800 border border-teal-200">
                        {{ formatRibuanSingkat(settings.pricing_monthly_price ?? 450000) }}
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-1">
                    <span class="text-xl font-black text-teal-800">{{ formatRupiah(metrics.estimated_mrr) }}</span>
                    <span class="text-[11px] text-slate-400 font-medium">/bln</span>
                </div>
            </div>

            <!-- Trial Clients -->
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Klien Trial</p>
                    <span class="rounded-lg bg-amber-50 p-2 text-amber-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-1.5">
                    <span class="text-2xl font-black text-slate-800">{{ metrics.trial_clients }}</span>
                    <span class="text-xs text-slate-500 font-medium">{{ currentTrialLimit }} req / hari</span>
                </div>
            </div>

            <!-- Total Requests Today -->
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Gateway Hari Ini</p>
                    <span class="rounded-lg bg-violet-50 p-2 text-violet-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-1.5">
                    <span class="text-2xl font-black text-violet-900">{{ metrics.total_gateway_requests_today }}</span>
                    <span class="text-xs text-slate-500 font-medium">panggilan API</span>
                </div>
            </div>
        </div>

        <!-- Top Navigation Tabs (Klien vs Bukti Pembayaran) -->
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-3">
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="rounded-xl px-4 py-2.5 text-xs font-bold transition flex items-center gap-2"
                    :class="adminActiveTab === 'clients' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'"
                    @click="adminActiveTab = 'clients'"
                >
                    <span>👥 Kelola Klien RS</span>
                    <span class="rounded bg-slate-700/80 px-2 py-0.5 text-[10px] font-mono text-white">{{ clients.length }}</span>
                </button>

                <button
                    type="button"
                    class="rounded-xl px-4 py-2.5 text-xs font-bold transition flex items-center gap-2"
                    :class="adminActiveTab === 'proofs' ? 'bg-teal-800 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'"
                    @click="adminActiveTab = 'proofs'"
                >
                    <span>💳 Bukti Pembayaran</span>
                    <span v-if="metrics.pending_proofs_count" class="rounded-full bg-rose-500 text-white px-2 py-0.5 text-[10px] font-black animate-pulse">
                        {{ metrics.pending_proofs_count }} Baru
                    </span>
                    <span v-else class="rounded bg-teal-100 text-teal-800 px-2 py-0.5 text-[10px] font-mono font-bold">{{ paymentProofs.length }}</span>
                </button>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-teal-800 px-3.5 py-2.5 text-xs font-bold text-white hover:bg-teal-900 transition shadow-2xs"
                    @click="showAdminUploadModal = true"
                >
                    <span>+ Upload Bukti Transfer Manual</span>
                </button>

                <a
                    :href="`https://t.me/${telegramHandle}`"
                    target="_blank"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-sky-500 hover:bg-sky-600 px-3.5 py-2.5 text-xs font-bold text-white transition shadow-2xs"
                >
                    <svg class="h-4 w-4 fill-white" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                    </svg>
                    <span>Telegram @{{ telegramHandle }}</span>
                </a>
            </div>
        </div>

        <!-- TAB 1: Clients List Section -->
        <div v-if="adminActiveTab === 'clients'">
            <!-- Filter & Search Toolbar -->
            <div class="mb-5 flex flex-col gap-3 rounded-2xl border border-slate-200/90 bg-white p-3.5 sm:p-4 shadow-xs sm:flex-row sm:items-center sm:justify-between">
                <!-- Search Bar -->
                <div class="relative w-full sm:max-w-md">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Cari faskes, email, atau no telepon..."
                        class="w-full rounded-xl border border-slate-200 bg-slate-50/60 py-2 pl-9 pr-3 text-xs font-medium text-slate-900 placeholder:text-slate-400 focus:border-teal-600 focus:bg-white focus:outline-none focus:ring-1 focus:ring-teal-600 transition"
                        @keyup.enter="applyFilters"
                    />
                    <svg class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <!-- Status Filter Segmented Control -->
                <div class="inline-flex items-center gap-1 rounded-xl bg-slate-100/90 p-1 border border-slate-200/70 text-xs overflow-x-auto self-start sm:self-auto">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 font-semibold transition whitespace-nowrap"
                        :class="currentStatus === 'all' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        @click="setStatusFilter('all')"
                    >
                        <span>Semua</span>
                        <span
                            class="rounded px-1.5 py-0.2 text-[10px] font-mono font-bold"
                            :class="currentStatus === 'all' ? 'bg-slate-100 text-slate-800' : 'bg-slate-200/70 text-slate-500'"
                        >
                            {{ metrics.total_clients }}
                        </span>
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 font-semibold transition whitespace-nowrap"
                        :class="currentStatus === 'active' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:text-emerald-700'"
                        @click="setStatusFilter('active')"
                    >
                        <span>Pro Aktif</span>
                        <span
                            class="rounded px-1.5 py-0.2 text-[10px] font-mono font-bold"
                            :class="currentStatus === 'active' ? 'bg-emerald-700 text-white' : 'bg-emerald-100 text-emerald-800'"
                        >
                            {{ metrics.active_subscribers }}
                        </span>
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 font-semibold transition whitespace-nowrap"
                        :class="currentStatus === 'trial' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        @click="setStatusFilter('trial')"
                    >
                        <span>Trial</span>
                        <span
                            class="rounded px-1.5 py-0.2 text-[10px] font-mono font-bold"
                            :class="currentStatus === 'trial' ? 'bg-slate-100 text-slate-800' : 'bg-slate-200/70 text-slate-500'"
                        >
                            {{ metrics.trial_clients }}
                        </span>
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 font-semibold transition whitespace-nowrap"
                        :class="currentStatus === 'expired' ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-600 hover:text-rose-700'"
                        @click="setStatusFilter('expired')"
                    >
                        <span>Expired</span>
                        <span
                            class="rounded px-1.5 py-0.2 text-[10px] font-mono font-bold"
                            :class="currentStatus === 'expired' ? 'bg-rose-700 text-white' : 'bg-rose-100 text-rose-800'"
                        >
                            {{ metrics.expired_clients }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- Clients Table Container -->
            <div class="rounded-2xl border border-slate-200/90 bg-white shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[960px] border-collapse text-left text-xs">
                        <!-- Table Header -->
                        <thead>
                            <tr class="border-b border-slate-200/80 bg-slate-50/75 text-[11px] font-bold uppercase tracking-wider text-slate-500 whitespace-nowrap">
                                <th scope="col" class="py-3.5 pl-6 pr-4">Rumah Sakit / Klien</th>
                                <th scope="col" class="py-3.5 px-4">Kontak</th>
                                <th scope="col" class="py-3.5 px-4">Status Lisensi</th>
                                <th scope="col" class="py-3.5 px-4">Pemakaian Hari Ini</th>
                                <th scope="col" class="py-3.5 px-4">Kunci API</th>
                                <th scope="col" class="py-3.5 pl-4 pr-6 text-right">Aksi</th>
                            </tr>
                        </thead>

                        <!-- Table Body -->
                        <tbody class="divide-y divide-slate-100">
                            <!-- Empty State -->
                            <tr v-if="clients.length === 0">
                                <td colspan="6" class="px-6 py-14 text-center">
                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 mb-3">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-bold text-slate-800">Belum ada faskes yang cocok</p>
                                    <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                                        Klien yang melakukan registrasi di <strong>/register</strong> akan otomatis terdaftar dan muncul di tabel ini.
                                    </p>
                                </td>
                            </tr>

                            <!-- Row Item -->
                            <tr
                                v-for="client in clients"
                                :key="client.id"
                                class="hover:bg-slate-50/70 transition-colors"
                            >
                                <!-- Col 1: Name & Identity -->
                                <td class="py-3.5 pl-6 pr-4 align-middle">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-800 font-bold text-sm border border-teal-200/80 shadow-2xs">
                                            {{ client.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-bold text-slate-900 text-sm leading-snug truncate max-w-xs sm:max-w-sm" :title="client.name">
                                                {{ client.name }}
                                            </div>
                                            <div class="flex items-center gap-1.5 mt-0.5 text-[11px] text-slate-500">
                                                <span class="font-mono font-medium text-slate-400">#{{ client.id }}</span>
                                                <span class="text-slate-300">•</span>
                                                <span class="truncate max-w-[160px]">{{ client.email }}</span>
                                                <span class="text-slate-300">•</span>
                                                <span class="text-slate-400 whitespace-nowrap">{{ client.created_at }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Col 2: Kontak & WhatsApp -->
                                <td class="py-3.5 px-4 align-middle whitespace-nowrap">
                                    <div v-if="client.phone" class="flex items-center gap-2">
                                        <span class="font-mono font-medium text-slate-800 text-xs">{{ client.phone }}</span>
                                        <a
                                            :href="`https://wa.me/${cleanPhoneForWa(client.phone)}?text=Halo%20Admin%20${encodeURIComponent(client.name)}%2C%20kami%20dari%20Tim%20SIMRS%20Gateway%20terkait%20layanan%20API%20BPJS.`"
                                            target="_blank"
                                            class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition shadow-2xs"
                                            title="Chat WhatsApp Klien"
                                        >
                                            <svg class="h-3 w-3 fill-current" viewBox="0 0 24 24">
                                                <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z" />
                                            </svg>
                                            <span>WA</span>
                                        </a>
                                    </div>
                                    <span v-else class="text-slate-300 font-mono text-xs select-none">—</span>
                                </td>

                                <!-- Col 3: Status Lisensi -->
                                <td class="py-3.5 px-4 align-middle whitespace-nowrap">
                                    <div v-if="client.subscription_status === 'active'" class="space-y-0.5">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-800 border border-emerald-200">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Pro Unlimited
                                        </span>
                                        <div class="text-[11px] text-slate-500">
                                            Sisa <strong class="text-emerald-700 font-semibold">{{ client.days_left }} hari</strong>
                                            <span class="text-slate-300"> • </span>
                                            <span>s/d {{ client.subscription_ends_at }}</span>
                                        </div>
                                    </div>

                                    <div v-else-if="client.subscription_status === 'expired'" class="space-y-0.5">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-800 border border-rose-200">
                                            <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                            Expired
                                        </span>
                                        <div class="text-[11px] text-rose-500">
                                            Habis sejak {{ client.subscription_ends_at }}
                                        </div>
                                    </div>

                                    <div v-else class="space-y-0.5">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700 border border-slate-200">
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                            Trial {{ currentTrialLimit }}/hari
                                        </span>
                                        <div class="text-[11px] text-slate-400">
                                            Reset harian 00:00 WIB
                                        </div>
                                    </div>
                                </td>

                                <!-- Col 4: Usage Today -->
                                <td class="py-3.5 px-4 align-middle">
                                    <div class="w-36 space-y-1.5">
                                        <div class="flex items-baseline justify-between text-xs">
                                            <span class="font-bold text-slate-900 tabular-nums">
                                                {{ Number(client.today_usage || 0).toLocaleString('id-ID') }}
                                            </span>
                                            <span class="text-[11px] font-medium text-slate-500 tabular-nums">
                                                {{ client.subscription_status === 'active' ? 'Unlimited' : `/ ${Number(currentTrialLimit).toLocaleString('id-ID')} req` }}
                                            </span>
                                        </div>
                                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100 border border-slate-200/60">
                                            <div
                                                class="h-full rounded-full transition-all duration-300"
                                                :class="client.subscription_status === 'active'
                                                    ? 'bg-teal-500'
                                                    : (client.today_usage >= currentTrialLimit ? 'bg-rose-500' : (client.today_usage > (currentTrialLimit * 0.8) ? 'bg-amber-500' : 'bg-teal-600'))"
                                                :style="{ width: client.subscription_status === 'active' ? '100%' : `${Math.min(100, (client.today_usage / currentTrialLimit) * 100)}%` }"
                                            ></div>
                                        </div>
                                        <p v-if="client.subscription_status !== 'active' && client.today_usage >= currentTrialLimit" class="text-[10px] font-bold text-rose-600">
                                            Limit habis (HTTP 429)
                                        </p>
                                        <p v-else-if="client.subscription_status !== 'active'" class="text-[10px] text-slate-400 tabular-nums">
                                            Tersisa {{ Math.max(0, currentTrialLimit - client.today_usage).toLocaleString('id-ID') }} request
                                        </p>
                                    </div>
                                </td>

                                <!-- Col 5: Sanctum API Key -->
                                <td class="py-3.5 px-4 align-middle whitespace-nowrap">
                                    <div class="space-y-0.5">
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-semibold"
                                            :class="client.tokens_count > 0 ? 'bg-slate-100 text-slate-800 border border-slate-200' : 'bg-amber-50 text-amber-800 border border-amber-200'"
                                        >
                                            <svg class="h-3 w-3 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                            </svg>
                                            <span>{{ client.tokens_count }} Token</span>
                                        </span>
                                        <p v-if="client.latest_token_created_at" class="text-[10px] text-slate-400 font-mono">
                                            {{ client.latest_token_created_at }}
                                        </p>
                                        <p v-else class="text-[10px] text-amber-600 font-medium">Belum generate</p>
                                    </div>
                                </td>

                                <!-- Col 6: Actions -->
                                <td class="py-3.5 pl-4 pr-6 align-middle text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- 1-Click +30 Days Extend -->
                                        <button
                                            type="button"
                                            :title="`Aktifkan / Perpanjang 30 Hari (${formatRupiah(settings.pricing_monthly_price ?? 450000)})`"
                                            :disabled="actionLoading === client.id"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-teal-800 px-2.5 py-1.5 text-xs font-semibold text-white shadow-2xs hover:bg-teal-900 disabled:opacity-50 transition"
                                            @click="extendSubscription(client.id, client.name, 30)"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span>+30 Hari</span>
                                        </button>

                                        <!-- Manage Client Settings Button (Opens Clean Modal) -->
                                        <button
                                            type="button"
                                            title="Buka Menu Manajemen Klien"
                                            class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition"
                                            @click="openManageModal(client)"
                                        >
                                            <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <span>Kelola</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div> <!-- End TAB 1: Clients List Section -->

        <!-- TAB 2: Payment Proofs Management Section -->
        <div v-else-if="adminActiveTab === 'proofs'" class="space-y-4">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <span>💳 Verifikasi Bukti Pembayaran Transfer</span>
                        <span v-if="metrics.pending_proofs_count" class="rounded-full bg-rose-500 text-white px-2 py-0.5 text-xs font-black animate-pulse">
                            {{ metrics.pending_proofs_count }} Menunggu Konfirmasi
                        </span>
                    </h2>
                    <p class="text-xs text-slate-600 mt-0.5">
                        Daftar foto/screenshot bukti transfer yang dikirim klien atau diunggah manual dari konfirmasi Telegram/WhatsApp.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-teal-800 px-3.5 py-2 text-xs font-bold text-white hover:bg-teal-900 transition shadow-2xs"
                        @click="showAdminUploadModal = true"
                    >
                        <span>+ Upload Bukti Manual</span>
                    </button>
                    <a
                        :href="`https://t.me/${telegramHandle}`"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-sky-500 hover:bg-sky-600 px-3 py-2 text-xs font-bold text-white transition shadow-2xs"
                    >
                        <span>Buka Telegram @{{ telegramHandle }}</span>
                    </a>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="paymentProofs.length === 0" class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center space-y-3">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-xl text-slate-500">
                    💳
                </div>
                <div>
                    <h3 class="text-xs font-bold text-slate-800">Belum Ada Bukti Pembayaran</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">Klien belum mengunggah bukti transfer, atau Anda dapat mengunggahnya secara manual.</p>
                </div>
                <button
                    type="button"
                    class="rounded-lg bg-teal-800 px-3.5 py-2 text-xs font-bold text-white hover:bg-teal-900 transition"
                    @click="showAdminUploadModal = true"
                >
                    Upload Bukti Transfer Manual
                </button>
            </div>

            <!-- Proofs Table -->
            <div v-else class="rounded-xl border border-slate-200 bg-white shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-600 border-b border-slate-200">
                            <tr>
                                <th class="p-3.5 font-bold">Klien Faskes</th>
                                <th class="p-3.5 font-bold">Paket & Nominal</th>
                                <th class="p-3.5 font-bold">Foto Bukti Transfer</th>
                                <th class="p-3.5 font-bold">Bank & Keterangan</th>
                                <th class="p-3.5 font-bold">Waktu & Pengunggah</th>
                                <th class="p-3.5 font-bold">Status</th>
                                <th class="p-3.5 font-bold text-right">Aksi Superadmin</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="proof in paymentProofs" :key="proof.id" class="hover:bg-slate-50/70 transition">
                                <td class="p-3.5">
                                    <div class="font-bold text-slate-900">{{ proof.client_name }}</div>
                                    <div class="text-[11px] text-slate-500 font-mono">{{ proof.client_email }}</div>
                                </td>
                                <td class="p-3.5">
                                    <div class="font-bold text-slate-900">{{ proof.plan_name }}</div>
                                    <div class="text-xs font-mono font-bold text-teal-800">{{ formatRupiah(proof.amount) }}</div>
                                </td>
                                <td class="p-3.5">
                                    <button
                                        v-if="proof.proof_url"
                                        type="button"
                                        class="group relative block h-14 w-20 overflow-hidden rounded-lg border border-slate-300 bg-slate-100 hover:ring-2 hover:ring-teal-500 transition"
                                        @click="selectedProofZoom = proof.proof_url"
                                        title="Klik untuk melihat foto penuh"
                                    >
                                        <img :src="proof.proof_url" :alt="proof.original_filename" class="h-full w-full object-cover group-hover:scale-105 transition" />
                                        <div class="absolute inset-0 bg-black/25 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-[10px] font-bold transition">
                                            🔍 Perbesar
                                        </div>
                                    </button>
                                    <span v-else class="text-slate-400 font-mono text-[10px]">-</span>
                                </td>
                                <td class="p-3.5 text-slate-600 max-w-xs">
                                    <div v-if="proof.bank_source" class="font-semibold text-slate-800">{{ proof.bank_source }}</div>
                                    <div v-if="proof.notes" class="text-[11px] text-slate-500 italic">{{ proof.notes }}</div>
                                    <div v-if="proof.admin_notes" class="text-[10px] text-amber-800 bg-amber-50 rounded p-1 mt-1 border border-amber-200">
                                        Catatan: {{ proof.admin_notes }}
                                    </div>
                                </td>
                                <td class="p-3.5 text-slate-500 text-[11px] whitespace-nowrap">
                                    <div>{{ proof.created_at }}</div>
                                    <span
                                        class="inline-block rounded px-1.5 py-0.2 text-[9px] font-mono font-bold uppercase mt-0.5"
                                        :class="proof.uploaded_by_role === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-slate-100 text-slate-700'"
                                    >
                                        Oleh: {{ proof.uploaded_by_role === 'admin' ? 'Superadmin' : 'Klien' }}
                                    </span>
                                    <div v-if="proof.verified_at" class="text-[10px] text-emerald-700 font-medium mt-0.5">Disetujui: {{ proof.verified_at }}</div>
                                </td>
                                <td class="p-3.5 whitespace-nowrap">
                                    <span
                                        v-if="proof.status === 'approved'"
                                        class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-700 border border-emerald-200"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Disetujui & Aktif
                                    </span>
                                    <span
                                        v-else-if="proof.status === 'rejected'"
                                        class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-bold text-rose-700 border border-rose-200"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                        Ditolak
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-bold text-amber-700 border border-amber-200"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        Menunggu Verifikasi
                                    </span>
                                </td>
                                <td class="p-3.5 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Approve Button -->
                                        <button
                                            v-if="proof.status !== 'approved'"
                                            type="button"
                                            class="inline-flex items-center gap-1 rounded-lg bg-teal-700 hover:bg-teal-800 text-white px-2.5 py-1 text-[11px] font-bold shadow-2xs transition"
                                            @click="approveProof(proof.id, 30)"
                                            title="Setujui dan otomatis perpanjang 30 hari"
                                        >
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span>Setujui +30H</span>
                                        </button>

                                        <!-- Reject Button -->
                                        <button
                                            v-if="proof.status === 'pending'"
                                            type="button"
                                            class="inline-flex items-center gap-1 rounded-lg border border-rose-300 bg-rose-50 hover:bg-rose-100 text-rose-700 px-2 py-1 text-[11px] font-bold transition"
                                            @click="rejectProof(proof.id)"
                                            title="Tolak bukti pembayaran"
                                        >
                                            <span>Tolak</span>
                                        </button>

                                        <!-- Delete Button -->
                                        <button
                                            type="button"
                                            class="rounded p-1 text-slate-400 hover:text-rose-600 transition"
                                            @click="deleteProof(proof.id)"
                                            title="Hapus riwayat"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Management Modal Dialog (No Overflow Bugs!) -->
        <div
            v-if="selectedClient"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4"
            @click.self="closeManageModal"
        >
            <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl transition-all">
                <!-- Modal Header -->
                <div class="flex items-start justify-between border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-50 text-teal-800 font-black text-sm border border-teal-200">
                            {{ selectedClient.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">{{ selectedClient.name }}</h3>
                            <p class="text-xs text-slate-500">{{ selectedClient.email }} • ID: {{ selectedClient.id }}</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition"
                        @click="closeManageModal"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="space-y-5 py-4">
                    <!-- Current Status Info -->
                    <div class="rounded-xl bg-slate-50 p-3.5 border border-slate-200 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 font-medium">Status Paket Saat Ini:</span>
                            <span
                                class="font-bold"
                                :class="selectedClient.subscription_status === 'active' ? 'text-emerald-700' : 'text-slate-700'"
                            >
                                {{ selectedClient.subscription_status === 'active' ? `Pro Aktif (s/d ${selectedClient.subscription_ends_at})` : `Trial (${currentTrialLimit} req/hari)` }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-200/60">
                            <span class="text-slate-500 font-medium">Pemakaian Hari Ini:</span>
                            <span class="font-bold text-slate-800">{{ selectedClient.today_usage }} Request</span>
                        </div>
                    </div>

                    <!-- Action Section 1: Extend Pro Subscription -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            1. Perpanjang Masa Aktif Pro ({{ formatRupiah(settings.pricing_monthly_price ?? 450000) }} / bln)
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                type="button"
                                :disabled="actionLoading === selectedClient.id"
                                class="rounded-lg border border-teal-600 bg-teal-50 px-3 py-2 text-xs font-bold text-teal-800 hover:bg-teal-100 transition shadow-2xs"
                                @click="extendSubscription(selectedClient.id, selectedClient.name, 30)"
                            >
                                +30 Hari (1 Bln)
                            </button>
                            <button
                                type="button"
                                :disabled="actionLoading === selectedClient.id"
                                class="rounded-lg border border-teal-600 bg-teal-50 px-3 py-2 text-xs font-bold text-teal-800 hover:bg-teal-100 transition shadow-2xs"
                                @click="extendSubscription(selectedClient.id, selectedClient.name, 60)"
                            >
                                +60 Hari (2 Bln)
                            </button>
                            <button
                                type="button"
                                :disabled="actionLoading === selectedClient.id"
                                class="rounded-lg border border-teal-600 bg-teal-50 px-3 py-2 text-xs font-bold text-teal-800 hover:bg-teal-100 transition shadow-2xs"
                                @click="extendSubscription(selectedClient.id, selectedClient.name, 90)"
                            >
                                +90 Hari (3 Bln)
                            </button>
                        </div>
                    </div>

                    <!-- Action Section 2: Reset to Trial -->
                    <div v-if="selectedClient.subscription_status === 'active'" class="border-t border-slate-100 pt-4">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            2. Kembalikan ke Trial (Non-aktifkan Pro)
                        </label>
                        <p class="text-[11px] text-slate-500 mb-2">
                            Jika faskes berhenti berlangganan, kembalikan akun mereka ke batas {{ currentTrialLimit }} request/hari.
                        </p>
                        <button
                            type="button"
                            :disabled="actionLoading === selectedClient.id"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-amber-300 bg-amber-50 px-3.5 py-2 text-xs font-bold text-amber-800 hover:bg-amber-100 transition"
                            @click="setTrial(selectedClient.id, selectedClient.name)"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>Kembalikan ke Paket Trial ({{ currentTrialLimit }}/hari)</span>
                        </button>
                    </div>

                    <!-- Action Section 3: Dangerous Actions -->
                    <div class="border-t border-slate-100 pt-4 space-y-2">
                        <label class="block text-xs font-bold text-rose-700 uppercase tracking-wider">
                            3. Tindakan Keamanan & Hapus
                        </label>
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                :disabled="actionLoading === selectedClient.id"
                                class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-100 transition"
                                @click="revokeTokens(selectedClient.id, selectedClient.name)"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                                <span>Cabut Semua API Key</span>
                            </button>

                            <button
                                type="button"
                                :disabled="actionLoading === selectedClient.id"
                                class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-bold text-rose-600 hover:bg-rose-50 hover:border-rose-300 transition"
                                @click="deleteClient(selectedClient.id, selectedClient.name)"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                <span>Hapus Akun Klien</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="mt-4 flex justify-end border-t border-slate-100 pt-3">
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition"
                        @click="closeManageModal"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- Admin Upload Payment Proof Modal -->
        <div
            v-if="showAdminUploadModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 overflow-y-auto"
            @click.self="showAdminUploadModal = false"
        >
            <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl transition-all my-8">
                <!-- Header -->
                <div class="flex items-start justify-between border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-100 text-purple-800 font-bold border border-purple-200">
                            💳
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Upload Bukti Transfer (Admin)</h3>
                            <p class="text-xs text-slate-500">Unggah bukti bayar faskes yang diterima via Telegram atau rekening bank</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition"
                        @click="showAdminUploadModal = false"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="submitAdminProof" class="space-y-4 py-4">
                    <!-- Error Alert -->
                    <div v-if="adminUploadError" class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700 flex items-center gap-2">
                        <svg class="h-4 w-4 shrink-0 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>{{ adminUploadError }}</span>
                    </div>

                    <!-- Client Select -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Pilih Klien Faskes / RS <span class="text-rose-500">*</span>
                        </label>
                        <select
                            v-model="adminProofForm.user_id"
                            required
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-800 shadow-2xs focus:border-teal-500 focus:outline-hidden focus:ring-1 focus:ring-teal-500"
                        >
                            <option value="" disabled>-- Pilih Akun Faskes --</option>
                            <option v-for="client in clients" :key="client.id" :value="client.id">
                                {{ client.name }} ({{ client.email }})
                            </option>
                        </select>
                    </div>

                    <!-- Grid: Plan & Amount -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Paket</label>
                            <input
                                v-model="adminProofForm.plan_name"
                                type="text"
                                required
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-800 shadow-2xs focus:border-teal-500 focus:outline-hidden"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nominal (Rp)</label>
                            <input
                                v-model.number="adminProofForm.amount"
                                type="number"
                                required
                                min="0"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-800 shadow-2xs focus:border-teal-500 focus:outline-hidden"
                            />
                        </div>
                    </div>

                    <!-- Bank Source & Notes -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Metode / Bank</label>
                            <input
                                v-model="adminProofForm.bank_source"
                                type="text"
                                placeholder="BCA / Mandiri / Telegram"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-800 shadow-2xs focus:border-teal-500 focus:outline-hidden"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Catatan</label>
                            <input
                                v-model="adminProofForm.notes"
                                type="text"
                                placeholder="Diterima via @FauziRahman95"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-800 shadow-2xs focus:border-teal-500 focus:outline-hidden"
                            />
                        </div>
                    </div>

                    <!-- STRICT IMAGE UPLOAD ONLY -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-bold text-slate-700">
                                File Foto Bukti Transfer <span class="text-rose-500">*</span>
                            </label>
                            <span class="rounded bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-700 border border-rose-200">
                                🔒 Hanya Gambar (JPG, PNG, WEBP)
                            </span>
                        </div>
                        <input
                            ref="adminFileInputRef"
                            type="file"
                            accept="image/png, image/jpeg, image/jpg, image/webp"
                            required
                            @change="onAdminFileSelected"
                            class="block w-full text-xs text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-teal-50 file:px-4 file:py-2 file:text-xs file:font-bold file:text-teal-800 hover:file:bg-teal-100 transition cursor-pointer"
                        />
                        <p class="text-[11px] text-slate-400 mt-1">Dokumen selain gambar (PDF, Zip, doc) otomatis ditolak oleh sistem keamanan.</p>

                        <!-- Preview Container -->
                        <div v-if="adminPreviewImage" class="mt-3 relative rounded-xl overflow-hidden border border-slate-200 bg-slate-50 max-h-48 flex items-center justify-center p-2">
                            <img :src="adminPreviewImage" alt="Preview Bukti Transfer" class="max-h-44 object-contain rounded-lg shadow-xs" />
                        </div>
                    </div>

                    <!-- Auto Approve Toggle -->
                    <div class="rounded-xl border border-teal-200 bg-teal-50/70 p-3.5 space-y-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="adminProofForm.auto_approve"
                                class="rounded border-slate-300 text-teal-700 focus:ring-teal-500 h-4 w-4"
                            />
                            <span class="text-xs font-bold text-teal-900">Langsung setujui dan aktifkan langganan</span>
                        </label>
                        <div v-if="adminProofForm.auto_approve" class="flex items-center gap-2 pl-6">
                            <span class="text-[11px] text-teal-800 font-medium">Tambah masa aktif:</span>
                            <select
                                v-model.number="adminProofForm.extend_days"
                                class="rounded-lg border border-teal-300 bg-white px-2 py-1 text-xs font-bold text-teal-900 shadow-2xs"
                            >
                                <option :value="30">30 Hari (1 Bulan)</option>
                                <option :value="60">60 Hari (2 Bulan)</option>
                                <option :value="90">90 Hari (3 Bulan)</option>
                                <option :value="365">365 Hari (1 Tahun)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-3">
                        <button
                            type="button"
                            class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition"
                            @click="showAdminUploadModal = false"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="adminProofForm.processing"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-teal-700 px-5 py-2 text-xs font-bold text-white hover:bg-teal-800 transition shadow-2xs disabled:opacity-50"
                        >
                            <svg v-if="adminProofForm.processing" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ adminProofForm.processing ? 'Menyimpan...' : 'Simpan & Unggah Bukti' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lightbox Zoom Modal for Proof of Payment -->
        <div
            v-if="selectedProofZoom"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
            @click.self="selectedProofZoom = null"
        >
            <div class="relative max-h-[90vh] max-w-4xl overflow-hidden rounded-2xl bg-white shadow-2xl">
                <!-- Top Bar -->
                <div class="flex items-center justify-between border-b border-slate-200 bg-slate-900 px-4 py-3 text-white">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-bold">Foto Bukti Transfer Pembayaran</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <a
                            :href="selectedProofZoom"
                            target="_blank"
                            download
                            class="rounded-lg bg-teal-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-teal-700 transition"
                        >
                            Unduh Foto
                        </a>
                        <button
                            type="button"
                            class="rounded-lg bg-slate-800 p-1.5 text-slate-300 hover:bg-slate-700 hover:text-white transition"
                            @click="selectedProofZoom = null"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Image View -->
                <div class="p-2 bg-slate-950 flex items-center justify-center max-h-[80vh] overflow-auto">
                    <img :src="selectedProofZoom" alt="Bukti Transfer Penuh" class="max-h-[75vh] w-auto object-contain rounded" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
