<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    settings: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const activeTab = ref('monthly');
const copiedCoupon = ref(false);

const initialTrialLimit = Number(props.settings.pricing_trial_limit ?? 50);
const rawTrialFeatures = props.settings.pricing_trial_features ?? [
    `⚠️ Limit ${initialTrialLimit} Request / Hari`,
    '✓ Akses Simulator UI (209 Endpoint)',
    '✓ Preview Data Mock Realistis',
    '✓ Tanpa Kartu Kredit / Tanpa Bayar',
];
const initialTrialFeatures = rawTrialFeatures.map(feat =>
    /Limit \d+ Request/i.test(feat) ? `⚠️ Limit ${initialTrialLimit} Request / Hari` : feat
);
const initialMonthlyDesc = (props.settings.pricing_monthly_desc ?? `Akses tanpa batas ${initialTrialLimit} req/hari, uji coba bebas semua modul sepuasnya.`)
    .replace(/Akses tanpa batas \d+ req\/hari/i, `Akses tanpa batas ${initialTrialLimit} req/hari`);
const initialTrialCta = (props.settings.pricing_trial_cta_text ?? `Daftar Uji Coba (${initialTrialLimit} Req / Hari)`)
    .replace(/Daftar Uji Coba \(\d+ Req \/ Hari\)/i, `Daftar Uji Coba (${initialTrialLimit} Req / Hari)`);

const form = useForm({
    // Monthly SaaS Plan
    pricing_monthly_title: props.settings.pricing_monthly_title ?? 'Lisensi Bulanan (SaaS)',
    pricing_monthly_tag: props.settings.pricing_monthly_tag ?? 'Akses Semaunya',
    pricing_monthly_desc: initialMonthlyDesc,
    pricing_monthly_price: Number(props.settings.pricing_monthly_price ?? 450000),
    pricing_monthly_strike_price: Number(props.settings.pricing_monthly_strike_price ?? 750000),
    pricing_monthly_promo_active: Boolean(props.settings.pricing_monthly_promo_active ?? true),
    pricing_monthly_promo_badge: props.settings.pricing_monthly_promo_badge ?? 'Promo Diskon 40%',
    pricing_monthly_promo_code: props.settings.pricing_monthly_promo_code ?? 'SIMRS450',
    pricing_monthly_period: props.settings.pricing_monthly_period ?? '/ bulan',
    pricing_monthly_features: [...(props.settings.pricing_monthly_features ?? [])],
    pricing_monthly_cta_text: props.settings.pricing_monthly_cta_text ?? 'Langganan Rp 450.000 / Bulan',

    // Trial Plan
    pricing_trial_title: props.settings.pricing_trial_title ?? 'Coba Gratis (Trial)',
    pricing_trial_tag: props.settings.pricing_trial_tag ?? 'Uji Coba Terbatas',
    pricing_trial_desc: props.settings.pricing_trial_desc ?? 'Untuk developer yang ingin mencoba simulator sebelum berlangganan.',
    pricing_trial_price: Number(props.settings.pricing_trial_price ?? 0),
    pricing_trial_limit: initialTrialLimit,
    pricing_trial_features: initialTrialFeatures,
    pricing_trial_cta_text: initialTrialCta,

    // Single Hospital License
    pricing_hospital_title: props.settings.pricing_hospital_title ?? 'Single Hospital',
    pricing_hospital_tag: props.settings.pricing_hospital_tag ?? 'Beli Putus Source Code',
    pricing_hospital_desc: props.settings.pricing_hospital_desc ?? 'Full source code untuk diinstalasi di server lokal intranet 1 Rumah Sakit.',
    pricing_hospital_price: Number(props.settings.pricing_hospital_price ?? 4500000),
    pricing_hospital_strike_price: Number(props.settings.pricing_hospital_strike_price ?? 6500000),
    pricing_hospital_promo_active: Boolean(props.settings.pricing_hospital_promo_active ?? true),
    pricing_hospital_promo_badge: props.settings.pricing_hospital_promo_badge ?? 'Hemat Rp 2 Juta',
    pricing_hospital_period: props.settings.pricing_hospital_period ?? '(Sekali Bayar)',
    pricing_hospital_features: [...(props.settings.pricing_hospital_features ?? [])],
    pricing_hospital_cta_text: props.settings.pricing_hospital_cta_text ?? 'Beli Source Code RS',

    // Global Promo Announcement Banner
    promo_banner_active: Boolean(props.settings.promo_banner_active ?? true),
    promo_banner_text: props.settings.promo_banner_text ?? '🎉 PROMO SPESIAL BULAN INI: Lisensi Bulanan Akses Semaunya hanya Rp 450.000/bln (Hemat Rp 300.000)! Gunakan kode promo SIMRS450.',
    promo_banner_link: props.settings.promo_banner_link ?? '#paket-lisensi',

    // Contact
    whatsapp_contact: props.settings.whatsapp_contact ?? '6281234567890',
    telegram_contact: props.settings.telegram_contact ?? 'FauziRahman95',
    support_email: props.settings.support_email ?? 'support@simrsgateway.id',

    // Rekening & QRIS Pembayaran
    payment_bank_name: props.settings.payment_bank_name ?? 'Bank BCA',
    payment_account_number: props.settings.payment_account_number ?? '8831-2940-11',
    payment_account_holder: props.settings.payment_account_holder ?? 'Ahmad Fauzi Rahman',
    payment_bank_name_2: props.settings.payment_bank_name_2 ?? '',
    payment_account_number_2: props.settings.payment_account_number_2 ?? '',
    payment_account_holder_2: props.settings.payment_account_holder_2 ?? '',
    payment_qris_active: Boolean(props.settings.payment_qris_active ?? true),
    payment_qris_image: props.settings.payment_qris_image ?? '/images/qris.jpeg',
});

const newFeatureInput = ref({
    monthly: '',
    trial: '',
    hospital: '',
});

const addFeature = (type) => {
    const val = newFeatureInput.value[type]?.trim();
    if (!val) return;
    if (type === 'monthly') form.pricing_monthly_features.push(val);
    if (type === 'trial') form.pricing_trial_features.push(val);
    if (type === 'hospital') form.pricing_hospital_features.push(val);
    newFeatureInput.value[type] = '';
};

const removeFeature = (type, index) => {
    if (type === 'monthly') form.pricing_monthly_features.splice(index, 1);
    if (type === 'trial') form.pricing_trial_features.splice(index, 1);
    if (type === 'hospital') form.pricing_hospital_features.splice(index, 1);
};

watch(() => form.pricing_trial_limit, (newLimit) => {
    if (!newLimit || newLimit < 1) return;
    form.pricing_trial_features = form.pricing_trial_features.map((feat) => {
        if (/Limit \d+ Request/i.test(feat)) {
            return `⚠️ Limit ${newLimit} Request / Hari`;
        }
        return feat;
    });
    if (/Akses tanpa batas \d+ req\/hari/i.test(form.pricing_monthly_desc)) {
        form.pricing_monthly_desc = form.pricing_monthly_desc.replace(/Akses tanpa batas \d+ req\/hari/i, `Akses tanpa batas ${newLimit} req/hari`);
    }
    if (/Daftar Uji Coba \(\d+ Req \/ Hari\)/i.test(form.pricing_trial_cta_text)) {
        form.pricing_trial_cta_text = `Daftar Uji Coba (${newLimit} Req / Hari)`;
    }
});

const formatRupiah = (num) => {
    if (num === null || num === undefined) return 'Rp 0';
    return 'Rp ' + Number(num).toLocaleString('id-ID');
};

const applyPreset = (preset) => {
    if (preset === 'promo_450') {
        form.pricing_monthly_price = 450000;
        form.pricing_monthly_strike_price = 750000;
        form.pricing_monthly_promo_active = true;
        form.pricing_monthly_promo_badge = 'Promo Diskon 40%';
        form.pricing_monthly_promo_code = 'SIMRS450';
        form.pricing_monthly_cta_text = 'Langganan Rp 450.000 / Bulan';
        form.promo_banner_active = true;
        form.promo_banner_text = '🎉 PROMO SPESIAL BULAN INI: Lisensi Bulanan Akses Semaunya hanya Rp 450.000/bln (Hemat Rp 300.000)! Gunakan kode promo SIMRS450.';
    } else if (preset === 'normal_750') {
        form.pricing_monthly_price = 750000;
        form.pricing_monthly_strike_price = 0;
        form.pricing_monthly_promo_active = false;
        form.pricing_monthly_promo_badge = '';
        form.pricing_monthly_cta_text = 'Langganan Rp 750.000 / Bulan';
    } else if (preset === 'legacy_115') {
        form.pricing_monthly_price = 115000;
        form.pricing_monthly_strike_price = 0;
        form.pricing_monthly_promo_active = false;
        form.pricing_monthly_cta_text = 'Langganan Rp 115.000 / Bulan';
    }
};

const submit = () => {
    form.post('/admin/pricing', {
        preserveScroll: true,
    });
};
</script>

<template>
    <AdminLayout
        title="Pengaturan Paket Lisensi & Promo"
        subtitle="Atur harga lisensi bulanan (SaaS), promo diskon, harga coret, kuota trial harian, dan banner pengumuman."
    >
        <Head title="Pengaturan Harga & Promo - SIMRS Gateway" />

        <div class="mx-auto max-w-7xl space-y-6">
            <!-- Top Controls Bar -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between rounded-xl bg-white p-4 shadow-xs border border-slate-200">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-teal-50 text-teal-700 ring-1 ring-teal-600/20">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-bold text-slate-900">Manajemen Harga & Kampanye Promo</h2>
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700 ring-1 ring-inset ring-emerald-600/30">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                Live Sync Aktif
                            </span>
                        </div>
                        <p class="text-xs text-slate-500">Perubahan langsung berlaku di Landing Page, Dashboard Klien, BPJS Tester, dan Pesan WhatsApp.</p>
                    </div>
                </div>

                <!-- Action Button & Presets -->
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        class="rounded-lg border border-teal-200 bg-teal-50 px-3 py-1.5 text-xs font-semibold text-teal-800 hover:bg-teal-100 transition"
                        @click="applyPreset('promo_450')"
                    >
                        ⚡ Preset Rp 450rb (Promo Diskon 40%)
                    </button>
                    <button
                        type="button"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-lg bg-teal-700 px-4 py-2 text-xs font-bold text-white hover:bg-teal-800 transition shadow-sm disabled:opacity-50"
                        @click="submit"
                    >
                        <svg v-if="form.processing" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Semua Pengaturan' }}</span>
                    </button>
                </div>
            </div>

            <!-- Flash Success Alert -->
            <div v-if="page.props.flash?.success" class="rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-xs font-semibold text-emerald-900 flex items-center gap-2">
                <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ page.props.flash.success }}</span>
            </div>

            <!-- Main Form Grid (2 Columns: Left Editor Tabs, Right Live Interactive Preview) -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <!-- LEFT COLUMN: Settings Editor Form (7 cols) -->
                <div class="lg:col-span-7 space-y-4">
                    <!-- Tab Switcher -->
                    <div class="flex overflow-x-auto rounded-xl border border-slate-200 bg-slate-100/80 p-1 text-xs font-semibold text-slate-600">
                        <button
                            type="button"
                            class="flex-1 rounded-lg py-2 px-3 transition whitespace-nowrap"
                            :class="activeTab === 'monthly' ? 'bg-white text-slate-900 shadow-xs font-bold' : 'hover:text-slate-900'"
                            @click="activeTab = 'monthly'"
                        >
                            💳 Paket Bulanan & Promo
                        </button>
                        <button
                            type="button"
                            class="flex-1 rounded-lg py-2 px-3 transition whitespace-nowrap"
                            :class="activeTab === 'hospital' ? 'bg-white text-slate-900 shadow-xs font-bold' : 'hover:text-slate-900'"
                            @click="activeTab = 'hospital'"
                        >
                            🏥 Single Hospital
                        </button>
                        <button
                            type="button"
                            class="flex-1 rounded-lg py-2 px-3 transition whitespace-nowrap"
                            :class="activeTab === 'trial' ? 'bg-white text-slate-900 shadow-xs font-bold' : 'hover:text-slate-900'"
                            @click="activeTab = 'trial'"
                        >
                            🎁 Trial & Limit
                        </button>
                        <button
                            type="button"
                            class="flex-1 rounded-lg py-2 px-3 transition whitespace-nowrap"
                            :class="activeTab === 'banner' ? 'bg-white text-slate-900 shadow-xs font-bold' : 'hover:text-slate-900'"
                            @click="activeTab = 'banner'"
                        >
                            📣 Banner & Kontak
                        </button>
                        <button
                            type="button"
                            class="flex-1 rounded-lg py-2 px-3 transition whitespace-nowrap"
                            :class="activeTab === 'payment' ? 'bg-white text-slate-900 shadow-xs font-bold' : 'hover:text-slate-900'"
                            @click="activeTab = 'payment'"
                        >
                            🏦 Rekening & QRIS
                        </button>
                    </div>

                    <!-- TAB 1: Monthly SaaS Plan & Promo -->
                    <div v-show="activeTab === 'monthly'" class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-5">
                        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Paket Lisensi Bulanan (SaaS / Akses Semaunya)</h3>
                                <p class="text-xs text-slate-500">Konfigurasi harga berlangganan bulanan dan badge promo diskon.</p>
                            </div>
                            <span class="rounded bg-teal-100 px-2 py-0.5 text-[11px] font-bold text-teal-800">Paket Utama</span>
                        </div>

                        <!-- Pricing & Promo Status Box -->
                        <div class="rounded-xl border border-teal-200 bg-teal-50/50 p-4 space-y-4">
                            <div class="flex items-center justify-between">
                                <label class="flex items-center gap-2 cursor-pointer select-none">
                                    <input
                                        v-model="form.pricing_monthly_promo_active"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-teal-300 text-teal-700 focus:ring-teal-600"
                                    >
                                    <span class="text-xs font-bold text-teal-900">Aktifkan Status Promo & Harga Coret</span>
                                </label>
                                <span
                                    class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase"
                                    :class="form.pricing_monthly_promo_active ? 'bg-rose-100 text-rose-700' : 'bg-slate-200 text-slate-600'"
                                >
                                    {{ form.pricing_monthly_promo_active ? 'Promo On' : 'Promo Off' }}
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">
                                        Harga Aktif / Promo (Rp)
                                        <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-xs font-bold text-slate-400">Rp</span>
                                        <input
                                            v-model.number="form.pricing_monthly_price"
                                            type="number"
                                            step="1000"
                                            class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm font-bold text-teal-800 outline-none focus:border-teal-600 focus:ring-1 focus:ring-teal-600"
                                        >
                                    </div>
                                    <p class="text-[10px] text-slate-500 mt-1">Harga yang dibayar klien saat ini (e.g. 450000).</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">
                                        Harga Normal / Harga Coret (Rp)
                                    </label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-xs font-bold text-slate-400">Rp</span>
                                        <input
                                            v-model.number="form.pricing_monthly_strike_price"
                                            type="number"
                                            step="1000"
                                            :disabled="!form.pricing_monthly_promo_active"
                                            class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm font-bold text-slate-600 outline-none focus:border-teal-600 focus:ring-1 focus:ring-teal-600 disabled:bg-slate-100 disabled:text-slate-400"
                                        >
                                    </div>
                                    <p class="text-[10px] text-slate-500 mt-1">Ditampilkan dicoret merah jika promo aktif (e.g. 750000).</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Badge Promo Diskon</label>
                                    <input
                                        v-model="form.pricing_monthly_promo_badge"
                                        type="text"
                                        placeholder="Promo Diskon 40%"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-800 outline-none focus:border-teal-600"
                                    >
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Kode Kupon Promo</label>
                                    <input
                                        v-model="form.pricing_monthly_promo_code"
                                        type="text"
                                        placeholder="SIMRS450"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-mono font-bold text-teal-800 uppercase outline-none focus:border-teal-600"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Basic Card Content -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Judul Paket</label>
                                <input
                                    v-model="form.pricing_monthly_title"
                                    type="text"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 outline-none focus:border-teal-600"
                                >
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Tag Header</label>
                                <input
                                    v-model="form.pricing_monthly_tag"
                                    type="text"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 outline-none focus:border-teal-600"
                                >
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 mb-1">Deskripsi Singkat</label>
                                <input
                                    v-model="form.pricing_monthly_desc"
                                    type="text"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 outline-none focus:border-teal-600"
                                >
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 mb-1">Teks Tombol CTA</label>
                                <input
                                    v-model="form.pricing_monthly_cta_text"
                                    type="text"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-900 outline-none focus:border-teal-600"
                                >
                            </div>
                        </div>

                        <!-- Feature Bullet Points -->
                        <div class="space-y-2 pt-2 border-t border-slate-100">
                            <label class="block text-xs font-bold text-slate-800">Daftar Fitur Paket Bulanan</label>
                            <div class="space-y-1.5">
                                <div
                                    v-for="(feat, idx) in form.pricing_monthly_features"
                                    :key="idx"
                                    class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs text-slate-800"
                                >
                                    <span class="flex-1 font-medium">{{ feat }}</span>
                                    <button
                                        type="button"
                                        class="text-rose-500 hover:text-rose-700 font-bold px-1"
                                        @click="removeFeature('monthly', idx)"
                                    >
                                        ✕
                                    </button>
                                </div>
                            </div>
                            <div class="flex gap-2 pt-1">
                                <input
                                    v-model="newFeatureInput.monthly"
                                    type="text"
                                    placeholder="Tambah poin fitur baru..."
                                    class="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs outline-none focus:border-teal-600"
                                    @keyup.enter="addFeature('monthly')"
                                >
                                <button
                                    type="button"
                                    class="rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-bold text-white hover:bg-slate-900 transition"
                                    @click="addFeature('monthly')"
                                >
                                    + Tambah
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: Single Hospital License -->
                    <div v-show="activeTab === 'hospital'" class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-5">
                        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Paket Single Hospital (Beli Putus Source Code)</h3>
                                <p class="text-xs text-slate-500">Lisensi full source code Laravel + Vue untuk intranet Rumah Sakit.</p>
                            </div>
                            <span class="rounded bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-700">Lisensi On-Premise</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Harga Beli Putus (Rp)</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-xs font-bold text-slate-400">Rp</span>
                                    <input
                                        v-model.number="form.pricing_hospital_price"
                                        type="number"
                                        step="50000"
                                        class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm font-bold text-slate-900 outline-none focus:border-teal-600"
                                    >
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Harga Normal / Coret (Rp)</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-xs font-bold text-slate-400">Rp</span>
                                    <input
                                        v-model.number="form.pricing_hospital_strike_price"
                                        type="number"
                                        step="50000"
                                        class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm font-bold text-slate-600 outline-none focus:border-teal-600"
                                    >
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Badge Promo</label>
                                <input
                                    v-model="form.pricing_hospital_promo_badge"
                                    type="text"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 outline-none focus:border-teal-600"
                                >
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Teks Tombol CTA</label>
                                <input
                                    v-model="form.pricing_hospital_cta_text"
                                    type="text"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-900 outline-none focus:border-teal-600"
                                >
                            </div>
                        </div>

                        <!-- Feature Bullet Points -->
                        <div class="space-y-2 pt-2 border-t border-slate-100">
                            <label class="block text-xs font-bold text-slate-800">Daftar Fitur Single Hospital</label>
                            <div class="space-y-1.5">
                                <div
                                    v-for="(feat, idx) in form.pricing_hospital_features"
                                    :key="idx"
                                    class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs text-slate-800"
                                >
                                    <span class="flex-1 font-medium">{{ feat }}</span>
                                    <button
                                        type="button"
                                        class="text-rose-500 hover:text-rose-700 font-bold px-1"
                                        @click="removeFeature('hospital', idx)"
                                    >
                                        ✕
                                    </button>
                                </div>
                            </div>
                            <div class="flex gap-2 pt-1">
                                <input
                                    v-model="newFeatureInput.hospital"
                                    type="text"
                                    placeholder="Tambah fitur source code baru..."
                                    class="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs outline-none focus:border-teal-600"
                                    @keyup.enter="addFeature('hospital')"
                                >
                                <button
                                    type="button"
                                    class="rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-bold text-white hover:bg-slate-900 transition"
                                    @click="addFeature('hospital')"
                                >
                                    + Tambah
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: Trial & Limit -->
                    <div v-show="activeTab === 'trial'" class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-5">
                        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Paket Coba Gratis (Trial) & Kuota Harian</h3>
                                <p class="text-xs text-slate-500">Batas maksimal request per hari untuk pengguna gratis / demo.</p>
                            </div>
                            <span class="rounded bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-800">Batas Kuota</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">
                                    Limit Kuota Harian (Request / Hari)
                                    <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model.number="form.pricing_trial_limit"
                                    type="number"
                                    min="1"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-bold text-amber-800 outline-none focus:border-teal-600"
                                >
                                <p class="text-[10px] text-slate-500 mt-1">Sistem otomatis me-reset kuota ini setiap pukul 00:00 WIB.</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Judul Paket Trial</label>
                                <input
                                    v-model="form.pricing_trial_title"
                                    type="text"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 outline-none focus:border-teal-600"
                                >
                            </div>
                        </div>

                        <!-- Feature Bullet Points -->
                        <div class="space-y-2 pt-2 border-t border-slate-100">
                            <label class="block text-xs font-bold text-slate-800">Daftar Fitur Trial</label>
                            <div class="space-y-1.5">
                                <div
                                    v-for="(feat, idx) in form.pricing_trial_features"
                                    :key="idx"
                                    class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs text-slate-800"
                                >
                                    <span class="flex-1 font-medium">{{ feat }}</span>
                                    <button
                                        type="button"
                                        class="text-rose-500 hover:text-rose-700 font-bold px-1"
                                        @click="removeFeature('trial', idx)"
                                    >
                                        ✕
                                    </button>
                                </div>
                            </div>
                            <div class="flex gap-2 pt-1">
                                <input
                                    v-model="newFeatureInput.trial"
                                    type="text"
                                    placeholder="Tambah fitur trial baru..."
                                    class="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs outline-none focus:border-teal-600"
                                    @keyup.enter="addFeature('trial')"
                                >
                                <button
                                    type="button"
                                    class="rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-bold text-white hover:bg-slate-900 transition"
                                    @click="addFeature('trial')"
                                >
                                    + Tambah
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: Promo Banner & Contact -->
                    <div v-show="activeTab === 'banner'" class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-5">
                        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Banner Pengumuman Promo & Kontak Sales</h3>
                                <p class="text-xs text-slate-500">Banner melayang di bagian teratas website untuk menarik atensi calon pembeli.</p>
                            </div>
                            <span class="rounded bg-rose-100 px-2 py-0.5 text-[11px] font-bold text-rose-700">Pemberitahuan</span>
                        </div>

                        <div class="space-y-4">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input
                                    v-model="form.promo_banner_active"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-slate-300 text-teal-700 focus:ring-teal-600"
                                >
                                <span class="text-xs font-bold text-slate-900">Tampilkan Banner Promo di Atas Landing Page & Dashboard</span>
                            </label>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Teks Pengumuman Promo</label>
                                <textarea
                                    v-model="form.promo_banner_text"
                                    rows="3"
                                    class="w-full rounded-lg border border-slate-300 bg-white p-3 text-xs text-slate-900 outline-none focus:border-teal-600"
                                    placeholder="🎉 PROMO SPESIAL BULAN INI..."
                                />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Nomor WhatsApp Sales (62)</label>
                                    <input
                                        v-model="form.whatsapp_contact"
                                        type="text"
                                        placeholder="6281234567890"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-mono text-slate-900 outline-none focus:border-teal-600"
                                    >
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Username Telegram Sales</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-2 text-slate-400 font-mono text-xs select-none">@</span>
                                        <input
                                            v-model="form.telegram_contact"
                                            type="text"
                                            placeholder="FauziRahman95"
                                            class="w-full rounded-lg border border-slate-300 bg-white pl-7 pr-3 py-2 text-xs font-mono text-slate-900 outline-none focus:border-teal-600"
                                        >
                                    </div>
                                    <a
                                        v-if="form.telegram_contact"
                                        :href="`https://t.me/${form.telegram_contact.replace('@', '')}`"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 text-[10px] text-sky-600 hover:text-sky-700 font-semibold mt-1"
                                    >
                                        <span>Buka t.me/{{ form.telegram_contact.replace('@', '') }} →</span>
                                    </a>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Email Dukungan</label>
                                    <input
                                        v-model="form.support_email"
                                        type="email"
                                        placeholder="support@simrsgateway.id"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 outline-none focus:border-teal-600"
                                    >
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 5: Payment Accounts & QRIS -->
                    <div v-show="activeTab === 'payment'" class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-5">
                        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Rekening Tujuan & QRIS Pembayaran</h3>
                                <p class="text-xs text-slate-500">Informasi pembayaran yang ditampilkan ke klien saat upgrade atau upload bukti transfer.</p>
                            </div>
                            <span class="rounded bg-teal-100 px-2 py-0.5 text-[11px] font-bold text-teal-800">Pembayaran</span>
                        </div>

                        <!-- Bank Utama -->
                        <div class="rounded-lg border border-slate-200 p-4 space-y-3 bg-slate-50/50">
                            <h4 class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                <span>🏦 Rekening Bank Utama</span>
                                <span class="rounded bg-teal-600 text-white text-[9px] px-1.5 py-0.2 font-bold uppercase">Wajib</span>
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Bank</label>
                                    <input
                                        v-model="form.payment_bank_name"
                                        type="text"
                                        placeholder="Bank BCA"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 outline-none focus:border-teal-600"
                                    >
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Rekening</label>
                                    <input
                                        v-model="form.payment_account_number"
                                        type="text"
                                        placeholder="8831-2940-11"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-mono font-bold text-slate-900 outline-none focus:border-teal-600"
                                    >
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Atas Nama Pemilik</label>
                                    <input
                                        v-model="form.payment_account_holder"
                                        type="text"
                                        placeholder="Ahmad Fauzi Rahman"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 outline-none focus:border-teal-600"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Bank Cadangan / Tambahan (Opsional) -->
                        <div class="rounded-lg border border-slate-200 p-4 space-y-3 bg-slate-50/50">
                            <h4 class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                <span>🏦 Rekening Bank Tambahan</span>
                                <span class="rounded bg-slate-200 text-slate-700 text-[9px] px-1.5 py-0.2 font-bold uppercase">Opsional</span>
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Bank 2</label>
                                    <input
                                        v-model="form.payment_bank_name_2"
                                        type="text"
                                        placeholder="Bank Mandiri / BNI / BRI"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 outline-none focus:border-teal-600"
                                    >
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor Rekening 2</label>
                                    <input
                                        v-model="form.payment_account_number_2"
                                        type="text"
                                        placeholder="123-00-0987654-3"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-mono font-bold text-slate-900 outline-none focus:border-teal-600"
                                    >
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Atas Nama Pemilik 2</label>
                                    <input
                                        v-model="form.payment_account_holder_2"
                                        type="text"
                                        placeholder="Ahmad Fauzi Rahman"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 outline-none focus:border-teal-600"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- QRIS Pembayaran -->
                        <div class="rounded-lg border border-slate-200 p-4 space-y-3 bg-slate-50/50">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                    <span>📱 QRIS Pembayaran Nasional</span>
                                    <span class="rounded bg-sky-100 text-sky-800 text-[9px] px-1.5 py-0.2 font-bold uppercase">Semua Bank & E-Wallet</span>
                                </h4>
                                <label class="flex items-center gap-2 cursor-pointer select-none">
                                    <input
                                        v-model="form.payment_qris_active"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-slate-300 text-teal-700 focus:ring-teal-600"
                                    >
                                    <span class="text-xs font-bold text-slate-800">Aktifkan QRIS</span>
                                </label>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Path / URL Gambar QRIS</label>
                                    <input
                                        v-model="form.payment_qris_image"
                                        type="text"
                                        placeholder="/images/qris.jpeg"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-mono text-slate-900 outline-none focus:border-teal-600"
                                    >
                                    <p class="text-[10px] text-slate-500 mt-1">Default file ada di: <code>public/images/qris.jpeg</code>.</p>
                                </div>

                                <div v-if="form.payment_qris_image" class="flex items-center gap-3 bg-white p-2.5 rounded-lg border border-slate-200">
                                    <img
                                        :src="form.payment_qris_image"
                                        alt="Preview QRIS"
                                        class="h-20 w-20 rounded border border-slate-200 object-contain bg-white shadow-2xs"
                                    >
                                    <div class="text-[11px] text-slate-600 space-y-0.5">
                                        <div class="font-bold text-slate-900">QRIS Siap Ditampilkan</div>
                                        <div>Scan via GoPay, OVO, Dana, ShopeePay, BCA, Mandiri, dll.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: Real-Time Interactive Live Preview (5 cols) -->
                <div class="lg:col-span-5 space-y-4">
                    <div class="sticky top-20 space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Pratinjau Langsung (Live Preview)</span>
                            <span class="rounded bg-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-700">Tampilan Landing Page</span>
                        </div>

                        <!-- Top Promo Banner Preview -->
                        <div
                            v-if="form.promo_banner_active"
                            class="rounded-xl bg-gradient-to-r from-teal-700 via-emerald-600 to-teal-800 p-3 text-xs text-white shadow-md flex items-center justify-between gap-2"
                        >
                            <div class="flex items-center gap-2 truncate">
                                <span class="h-2 w-2 rounded-full bg-amber-400 animate-ping shrink-0"></span>
                                <span class="truncate font-medium text-[11px]">{{ form.promo_banner_text }}</span>
                            </div>
                            <span class="shrink-0 rounded bg-white/20 px-2 py-0.5 text-[10px] font-bold">Klaim Promo</span>
                        </div>

                        <!-- Card Preview (Monthly SaaS Package) -->
                        <div class="rounded-2xl border-2 border-teal-600 bg-white p-6 shadow-xl ring-4 ring-teal-50 relative overflow-hidden">
                            <!-- Promo Ribbon Tag -->
                            <div v-if="form.pricing_monthly_promo_active" class="absolute -top-1 -right-1 bg-gradient-to-r from-rose-500 to-amber-500 text-white font-extrabold text-[10px] px-4 py-1 rounded-bl-xl shadow-xs uppercase tracking-wider">
                                {{ form.pricing_monthly_promo_badge || 'PROMO SPESIAL' }}
                            </div>

                            <div class="space-y-4">
                                <span class="rounded bg-teal-100 px-2.5 py-0.5 text-[11px] font-bold text-teal-800">
                                    {{ form.pricing_monthly_tag }}
                                </span>
                                <h3 class="text-xl font-bold text-slate-900">{{ form.pricing_monthly_title }}</h3>
                                <p class="text-xs text-slate-600 leading-relaxed">{{ form.pricing_monthly_desc }}</p>

                                <div class="pt-2">
                                    <!-- Strike Price if Promo Active -->
                                    <div v-if="form.pricing_monthly_promo_active && form.pricing_monthly_strike_price" class="flex items-center gap-2 mb-1">
                                        <span class="text-sm font-bold text-rose-500 line-through">{{ formatRupiah(form.pricing_monthly_strike_price) }}</span>
                                        <span class="rounded bg-rose-50 px-1.5 py-0.2 text-[10px] font-bold text-rose-700 ring-1 ring-inset ring-rose-600/20">Hemat {{ formatRupiah(form.pricing_monthly_strike_price - form.pricing_monthly_price) }}</span>
                                    </div>
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-3xl font-extrabold text-teal-800">{{ formatRupiah(form.pricing_monthly_price) }}</span>
                                        <span class="text-xs text-slate-500 font-semibold">{{ form.pricing_monthly_period }}</span>
                                    </div>
                                </div>

                                <!-- Promo Coupon Code Preview -->
                                <div v-if="form.pricing_monthly_promo_code" class="flex items-center justify-between rounded-lg border border-dashed border-teal-300 bg-teal-50/70 px-3 py-2 text-xs">
                                    <div class="flex items-center gap-1.5 text-teal-900 font-medium">
                                        <svg class="h-4 w-4 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                        </svg>
                                        <span>Kupon: <strong class="font-mono font-bold">{{ form.pricing_monthly_promo_code }}</strong></span>
                                    </div>
                                    <span class="text-[10px] text-teal-700 font-bold">Diskon Aktif</span>
                                </div>

                                <ul class="space-y-2 text-xs text-slate-700 pt-3 border-t border-slate-100 font-medium">
                                    <li v-for="(f, i) in form.pricing_monthly_features" :key="i">
                                        {{ f }}
                                    </li>
                                </ul>
                            </div>

                            <div class="mt-6">
                                <button
                                    type="button"
                                    class="block w-full text-center rounded-xl bg-teal-800 py-3 text-xs font-bold text-white hover:bg-teal-900 transition shadow-md"
                                >
                                    {{ form.pricing_monthly_cta_text }}
                                </button>
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-[11px] text-slate-500 flex items-center justify-between">
                            <span>Limit Uji Coba Gratis: <strong>{{ form.pricing_trial_limit }} Req / Hari</strong></span>
                            <span>Single Hospital: <strong>{{ formatRupiah(form.pricing_hospital_price) }}</strong></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
