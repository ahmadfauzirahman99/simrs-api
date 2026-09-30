<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    client: {
        type: Object,
        required: true,
    },
    endpoints: {
        type: Object,
        required: true,
    },
    whatsappContact: {
        type: String,
        default: '6281234567890',
    },
    telegramContact: {
        type: String,
        default: 'FauziRahman95',
    },
    paymentProofs: {
        type: Array,
        default: () => [],
    },
    recentLogs: {
        type: Array,
        default: () => [],
    },
    postmanUrl: {
        type: String,
        default: '/downloads/postman-collection',
    },
});

const activeCodeTab = ref('curl');
const copiedField = ref('');
const isMobileMenuOpen = ref(false);

// Payment Proof Upload Modal & Form
const showUploadModal = ref(false);
const previewImage = ref(null);
const fileInputRef = ref(null);
const uploadError = ref('');
const selectedProofImageModal = ref(null);
const paymentMethodTab = ref('qris'); // 'qris' | 'bank'
const showQrisModal = ref(false);

const proofForm = useForm({
    plan_name: 'Lisensi Bulanan (SaaS) - Akses Semaunya',
    amount: 450000,
    bank_source: '',
    notes: '',
    proof_image: null,
});

const onFileSelected = (event) => {
    uploadError.value = '';
    const file = event.target.files?.[0];
    if (!file) {
        proofForm.proof_image = null;
        previewImage.value = null;
        return;
    }

    // STRICT VALIDATION: ONLY IMAGES ALLOWED!
    const allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    if (!allowedMimes.includes(file.type) && !file.type.startsWith('image/')) {
        uploadError.value = 'Hanya file gambar (JPG, JPEG, PNG, WEBP) yang diperbolehkan! File dokumen/format lain tidak dapat diproses.';
        event.target.value = '';
        proofForm.proof_image = null;
        previewImage.value = null;
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        uploadError.value = 'Ukuran file gambar maksimal 5 MB.';
        event.target.value = '';
        proofForm.proof_image = null;
        previewImage.value = null;
        return;
    }

    proofForm.proof_image = file;
    const reader = new FileReader();
    reader.onload = (e) => {
        previewImage.value = e.target.result;
    };
    reader.readAsDataURL(file);
};

const submitPaymentProof = () => {
    if (!proofForm.proof_image) {
        uploadError.value = 'Silakan pilih file gambar bukti transfer terlebih dahulu.';
        return;
    }

    proofForm.post('/client/payment-proofs', {
        preserveScroll: true,
        onSuccess: () => {
            showUploadModal.value = false;
            proofForm.reset('proof_image', 'notes', 'bank_source');
            previewImage.value = null;
            if (fileInputRef.value) fileInputRef.value.value = '';
        },
    });
};

// Live timer for midnight quota reset
const currentTime = ref(new Date());
let resetTimerInterval = null;

onMounted(() => {
    resetTimerInterval = setInterval(() => {
        currentTime.value = new Date();
    }, 60000);
});

onUnmounted(() => {
    if (resetTimerInterval) clearInterval(resetTimerInterval);
});

const nextResetInfo = computed(() => {
    const now = currentTime.value;
    const tomorrow = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1, 0, 0, 0);
    const diffMs = Math.max(0, tomorrow.getTime() - now.getTime());
    const diffHrs = Math.floor(diffMs / (1000 * 60 * 60));
    const diffMins = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));

    const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    const dayName = dayNames[tomorrow.getDay()];
    const dateNum = tomorrow.getDate();
    const monthName = monthNames[tomorrow.getMonth()];
    const yearNum = tomorrow.getFullYear();

    return {
        dateStr: `${dayName}, ${dateNum} ${monthName} ${yearNum}`,
        timeStr: '00:00 WIB',
        remainingStr: diffHrs > 0 ? `${diffHrs} jam ${diffMins} menit lagi` : `${diffMins} menit lagi`,
    };
});

const copyToClipboard = (text, fieldName) => {
    navigator.clipboard.writeText(text);
    copiedField.value = fieldName;
    setTimeout(() => {
        copiedField.value = '';
    }, 2000);
};

const regenerateApiKey = () => {
    if (confirm('Apakah Anda yakin ingin membuat ulang API Key? API Key lama tidak akan bisa digunakan lagi.')) {
        router.post('/client/api-keys/regenerate');
    }
};

const logout = () => {
    router.post('/logout');
};

const page = usePage();
const settings = computed(() => page.props.site_settings ?? {});
const flashSuccess = computed(() => page.props.flash?.success);

const formatRupiah = (num) => {
    if (num === null || num === undefined) return 'Rp 0';
    return 'Rp ' + Number(num).toLocaleString('id-ID');
};

const telegramHandle = computed(() => {
    return (props.telegramContact || settings.value.telegram_contact || 'FauziRahman95').replace('@', '');
});

const telegramChatUrl = computed(() => {
    const handle = telegramHandle.value;
    const text = encodeURIComponent(`Halo mas @${handle}, saya dari faskes ${props.client.name} (${props.client.email}). Saya ingin konfirmasi pembayaran lisensi SIMRS Gateway.`);
    return `https://t.me/${handle}?text=${text}`;
});

const telegramUpgradeUrl = computed(() => {
    const handle = telegramHandle.value;
    const priceStr = formatRupiah(settings.value.pricing_monthly_price ?? 450000);
    const promoCode = settings.value.pricing_monthly_promo_code ? ` (Kode Promo: ${settings.value.pricing_monthly_promo_code})` : '';
    const text = encodeURIComponent(`Halo mas @${handle}, saya dari faskes ${props.client.name} (${props.client.email}). Saya ingin upgrade lisensi bulanan (${priceStr} / bln)${promoCode} agar mendapatkan akses semaunya tanpa batas harian.`);
    return `https://t.me/${handle}?text=${text}`;
});

const codeSnippets = computed(() => {
    const token = props.client.api_key;
    const pesertaUrl = props.endpoints.bpjs_peserta;

    return {
        curl: `# 1. Contoh Request cURL Menggunakan API Key Anda:
curl -X GET "${pesertaUrl}?nomor=0001234567890&tipe=1" \\
  -H "Accept: application/json" \\
  -H "Authorization: Bearer ${token}"`,

        php: `<?php
// 2. Integrasi Menggunakan Laravel Http Client / Guzzle:
use Illuminate\\Support\\Facades\\Http;

$response = Http::withToken('${token}')
    ->acceptJson()
    ->get('${pesertaUrl}', [
        'nomor' => '0001234567890',
        'tipe'  => '1', // 1: No Kartu BPJS, 2: NIK
    ]);

if ($response->successful()) {
    $peserta = $response->json('response.peserta');
    echo "Nama Pasien: " . $peserta['nama'];
} else {
    echo "Error: " . $response->json('metaData.message');
}`,

        js: `// 3. Integrasi Node.js / Axios:
const axios = require('axios');

async function getPesertaBpjs() {
  try {
    const res = await axios.get('${pesertaUrl}', {
      params: { nomor: '0001234567890', tipe: '1' },
      headers: {
        'Authorization': 'Bearer ${token}',
        'Accept': 'application/json'
      }
    });

    console.log('Peserta:', res.data.response.peserta);
  } catch (error) {
    console.error('Request failed:', error.response?.data || error.message);
  }
}

getPesertaBpjs();`,

        python: `# 4. Integrasi Python Requests:
import requests

url = '${pesertaUrl}'
headers = {
    'Authorization': 'Bearer ${token}',
    'Accept': 'application/json'
}
params = {
    'nomor': '0001234567890',
    'tipe': '1'
}

response = requests.get(url, headers=headers, params=params)
data = response.json()

if response.status_code == 200:
    print('Nama:', data['response']['peserta']['nama'])
else:
    print('Error:', data['metaData']['message'])`
    };
});
</script>

<template>
    <Head title="Portal Klien & Kredensial API - SIMRS Gateway" />

    <div class="min-h-screen bg-slate-50 text-slate-900 font-sans selection:bg-teal-700 selection:text-white">
        <!-- Top App Bar -->
        <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 shadow-xs backdrop-blur-md">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-3 sm:px-6 lg:px-8">
                <!-- Brand Info -->
                <div class="flex items-center gap-2.5 sm:gap-3">
                    <Link href="/" class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-800 text-white font-bold shadow-xs hover:bg-teal-900 transition" title="Kembali ke Beranda">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </Link>
                    <div>
                        <div class="flex items-center gap-1.5 sm:gap-2">
                            <span class="text-sm font-bold text-slate-900">SIMRS Gateway Console</span>
                            <span class="rounded bg-teal-50 px-1.5 py-0.5 text-[10px] font-bold text-teal-800 border border-teal-200">Portal Klien</span>
                        </div>
                        <p class="text-[10px] sm:text-[11px] text-slate-500 font-medium hidden sm:block">Manajemen API Key & Kuota Bridging</p>
                    </div>
                </div>

                <!-- Desktop Action Buttons -->
                <div class="hidden md:flex items-center gap-2 sm:gap-2.5">
                    <!-- Beranda Button -->
                    <Link
                        href="/"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition"
                    >
                        <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span>Beranda</span>
                    </Link>

                    <Link
                        v-if="page.props.auth?.user?.is_superadmin"
                        href="/admin/dashboard"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-bold text-white shadow-xs hover:bg-slate-800 transition"
                    >
                        <svg class="h-3.5 w-3.5 text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span>Dashboard Superadmin</span>
                    </Link>

                    <Link
                        href="/admin/bpjs-tester"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition"
                    >
                        <svg class="h-3.5 w-3.5 text-teal-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Buka BPJS Studio UI</span>
                    </Link>

                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200 transition"
                        @click="logout"
                    >
                        <span>Keluar</span>
                    </button>
                </div>

                <!-- Mobile Action Header Buttons -->
                <div class="flex md:hidden items-center gap-1.5">
                    <Link
                        href="/admin/bpjs-tester"
                        class="inline-flex items-center gap-1 rounded-lg bg-teal-800 px-2.5 py-1.5 text-xs font-bold text-white shadow-2xs"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Studio</span>
                    </Link>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center h-9 w-9 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 transition shadow-2xs focus:outline-hidden"
                        @click="isMobileMenuOpen = !isMobileMenuOpen"
                        :aria-expanded="isMobileMenuOpen"
                        aria-label="Toggle navigation menu"
                    >
                        <svg v-if="!isMobileMenuOpen" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg v-else class="h-5 w-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Drawer -->
            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div
                    v-if="isMobileMenuOpen"
                    class="md:hidden border-b border-slate-200 bg-white/98 shadow-xl backdrop-blur-md px-4 py-4 space-y-3 max-h-[calc(100vh-4.5rem)] overflow-y-auto"
                >
                    <!-- Client Status Card -->
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-900 truncate pr-2">{{ client.name }}</span>
                            <span
                                v-if="client.is_unlimited"
                                class="rounded bg-emerald-100 text-emerald-800 text-[10px] px-2 py-0.5 font-bold shrink-0"
                            >
                                Pro Aktif
                            </span>
                            <span
                                v-else
                                class="rounded bg-amber-100 text-amber-800 text-[10px] px-2 py-0.5 font-bold shrink-0"
                            >
                                Trial ({{ client.quota_limit }}/hari)
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">{{ client.email }}</p>
                    </div>

                    <!-- Links List -->
                    <div class="space-y-1">
                        <Link
                            href="/"
                            @click="isMobileMenuOpen = false"
                            class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition"
                        >
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-600 shrink-0">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                            </div>
                            <span>Beranda (Halaman Utama)</span>
                        </Link>

                        <Link
                            v-if="page.props.auth?.user?.is_superadmin"
                            href="/admin/dashboard"
                            @click="isMobileMenuOpen = false"
                            class="flex items-center gap-3 rounded-xl bg-slate-900 text-white px-3.5 py-2.5 text-xs font-bold transition shadow-xs"
                        >
                            <svg class="h-4 w-4 text-teal-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            <span>Dashboard Superadmin</span>
                        </Link>

                        <Link
                            href="/admin/bpjs-tester"
                            @click="isMobileMenuOpen = false"
                            class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition"
                        >
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-teal-50 text-teal-700 shrink-0">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span>Buka BPJS Studio UI</span>
                        </Link>

                        <button
                            type="button"
                            @click="showUploadModal = true; isMobileMenuOpen = false"
                            class="w-full flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-teal-800 bg-teal-50 hover:bg-teal-100 transition text-left"
                        >
                            <span class="text-base">💳</span>
                            <span>Upload Bukti Transfer Pembayaran</span>
                        </button>

                        <a
                            :href="telegramChatUrl"
                            target="_blank"
                            class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-sky-800 bg-sky-50 hover:bg-sky-100 transition"
                        >
                            <svg class="h-4 w-4 fill-sky-600 shrink-0" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                            </svg>
                            <span>Chat Telegram @{{ telegramHandle }}</span>
                        </a>
                    </div>

                    <!-- Sign Out Button -->
                    <div class="pt-2 border-t border-slate-100">
                        <button
                            type="button"
                            @click="logout"
                            class="w-full flex items-center justify-center gap-2 rounded-xl bg-slate-100 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-rose-50 hover:text-rose-600 transition"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            <span>Keluar dari Portal Klien</span>
                        </button>
                    </div>
                </div>
            </transition>
        </header>

        <!-- Main Workspace -->
        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 space-y-6">
            <!-- Flash Alert -->
            <div v-if="flashSuccess" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 shadow-xs flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">✓</span>
                    <span class="font-semibold">{{ flashSuccess }}</span>
                </div>
                <button type="button" @click="page.props.flash.success = null" class="text-emerald-700 font-bold hover:text-emerald-900">&times;</button>
            </div>

            <!-- Top Hero Banner: Account & License Status -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-xl font-bold text-slate-900">{{ client.name }}</h1>
                        <span
                            v-if="client.is_unlimited"
                            class="rounded-full bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 text-xs font-bold text-emerald-800"
                        >
                            ✓ Akses Semaunya (Unlimited Pro)
                        </span>
                        <span
                            v-else
                            class="rounded-full bg-amber-50 border border-amber-200 px-2.5 py-0.5 text-xs font-bold text-amber-800"
                        >
                            Mode Uji Coba (Limit {{ client.quota_limit }} Req / Hari)
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500">
                        <span>Email: <strong class="text-slate-700">{{ client.email }}</strong></span>
                        <span v-if="client.phone">Kontak / HP: <strong class="text-slate-700">{{ client.phone }}</strong></span>
                        <span>Terdaftar: <strong class="text-slate-700">{{ client.created_at }}</strong></span>
                    </div>
                </div>

                <!-- Action Buttons: Upload Proof & Telegram & Upgrade -->
                <div class="shrink-0 flex flex-wrap items-center gap-2.5">
                    <!-- Upload Proof Button -->
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl border border-teal-300 bg-teal-50 px-3.5 py-2.5 text-xs font-bold text-teal-800 hover:bg-teal-100 transition shadow-2xs"
                        @click="showUploadModal = true"
                    >
                        <span>💳 Upload Bukti Transfer</span>
                    </button>

                    <!-- Telegram Chat Button -->
                    <a
                        :href="telegramChatUrl"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-sky-500 hover:bg-sky-600 px-3.5 py-2.5 text-xs font-bold text-white transition shadow-sm"
                        title="Chat Telegram ke @FauziRahman95"
                    >
                        <svg class="h-4 w-4 fill-white" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                        </svg>
                        <span>Telegram @{{ telegramHandle }}</span>
                    </a>

                    <!-- Upgrade Button via Telegram -->
                    <a
                        v-if="!client.is_unlimited"
                        :href="telegramUpgradeUrl"
                        target="_blank"
                        class="inline-flex items-center gap-2 rounded-xl bg-teal-800 px-4 py-2.5 text-xs font-bold text-white hover:bg-teal-900 transition shadow-sm"
                        title="Upgrade lisensi via Telegram"
                    >
                        <span>Upgrade ({{ formatRupiah(settings.pricing_monthly_price ?? 450000) }})</span>
                        <span v-if="settings.pricing_monthly_promo_active" class="rounded bg-amber-400 text-slate-900 text-[9px] px-1.5 py-0.5 font-black uppercase">
                            {{ settings.pricing_monthly_promo_badge || 'Promo' }}
                        </span>
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                    <div v-else class="text-right text-xs">
                        <span class="text-emerald-700 font-semibold block">Masa Aktif Langganan:</span>
                        <span class="font-bold text-slate-900">{{ client.subscription_ends_at }}</span>
                    </div>
                </div>
            </div>

            <!-- Two-Column Grid: Quota & Credentials -->
            <div class="grid gap-6 md:grid-cols-2 items-stretch">
                <!-- Card 1: API Key Credentials -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs flex flex-col justify-between space-y-4">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                                <svg class="h-4 w-4 text-teal-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                </svg>
                                <span>API Key (Bearer Token)</span>
                            </h2>
                            <button
                                type="button"
                                class="text-[11px] font-bold text-rose-600 hover:text-rose-700 hover:underline transition"
                                @click="regenerateApiKey"
                            >
                                Buat Ulang Token
                            </button>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Gunakan token ini pada Header <code class="rounded bg-slate-100 px-1 py-0.5 font-mono text-slate-800">Authorization: Bearer &lt;TOKEN&gt;</code> saat melakukan request dari aplikasi SIMRS lokal Anda.
                        </p>

                        <!-- Key Box -->
                        <div class="flex items-center justify-between rounded-xl border border-slate-300 bg-slate-50 p-3 font-mono text-xs text-slate-900 overflow-hidden">
                            <span class="truncate select-all pr-2">{{ client.api_key }}</span>
                            <button
                                type="button"
                                class="shrink-0 rounded bg-white px-2.5 py-1 text-xs font-bold text-slate-700 border border-slate-200 hover:bg-slate-100 transition shadow-2xs"
                                @click="copyToClipboard(client.api_key, 'apikey')"
                            >
                                {{ copiedField === 'apikey' ? '✓ Tersalin' : 'Salin Token' }}
                            </button>
                        </div>
                    </div>

                    <div class="rounded-xl border border-teal-100 bg-teal-50/60 p-3 text-[11px] text-teal-900 space-y-1">
                        <strong class="font-bold block">Base URL Gateway:</strong>
                        <div class="flex items-center justify-between font-mono">
                            <span class="truncate">{{ endpoints.base_url }}</span>
                            <button
                                type="button"
                                class="font-bold text-teal-700 hover:underline ml-2"
                                @click="copyToClipboard(endpoints.base_url, 'base_url')"
                            >
                                {{ copiedField === 'base_url' ? '✓' : 'Salin' }}
                            </button>
                        </div>
                    </div>

                    <!-- Postman Collection 1-Click Download -->
                    <div class="rounded-xl border border-amber-200 bg-amber-50/70 p-3 text-xs flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-500 text-white font-bold text-xs shadow-2xs">
                                P
                            </div>
                            <div>
                                <span class="font-bold text-amber-950 block">Postman Collection JSON</span>
                                <span class="text-[10px] text-amber-700">Format v2.1.0 • Siap Import & Langsung Pakai</span>
                            </div>
                        </div>
                        <a
                            :href="postmanUrl || '/downloads/postman-collection'"
                            download="simrs-gateway-bpjs-postman-collection.json"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-amber-700 transition shadow-2xs shrink-0"
                            title="Unduh file Postman Collection JSON lengkap"
                        >
                            <span>Unduh JSON</span>
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Card 2: Quota & Usage Telemetry -->
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs flex flex-col justify-between space-y-4">
                    <div class="space-y-3">
                        <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <svg class="h-4 w-4 text-teal-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <span>Status Pemakaian Hari Ini</span>
                        </h2>

                        <div v-if="client.is_unlimited" class="space-y-2 py-3">
                            <div class="flex items-center gap-3 text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl p-4">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-600 text-white font-bold shrink-0">
                                    ∞
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold">Akses Semaunya (Unlimited)</h3>
                                    <p class="text-xs text-emerald-700">Tidak ada batas harian. Bebas memanggil semua 209 endpoint tanpa henti.</p>
                                </div>
                            </div>
                        </div>

                        <div v-else class="space-y-3 py-1">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-600">Request Terpakai Hari Ini:</span>
                                <span class="font-mono font-bold text-slate-900">{{ client.quota_used }} / {{ client.quota_limit }}</span>
                            </div>

                            <!-- Progress Bar -->
                            <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden border border-slate-200">
                                <div
                                    class="bg-amber-600 h-3 rounded-full transition-all duration-300"
                                    :style="{ width: `${Math.min(100, (client.quota_used / (client.quota_limit || 1)) * 100)}%` }"
                                ></div>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-3 space-y-1.5 text-xs">
                                <div class="flex items-center justify-between text-slate-700">
                                    <span>Sisa Kuota Uji Coba Hari Ini:</span>
                                    <strong class="font-mono text-slate-900 font-bold">{{ client.quota_remaining }} request</strong>
                                </div>
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between text-[11px] text-slate-500 pt-1.5 border-t border-slate-200/60 gap-1">
                                    <span>Dapat diakses kembali pada:</span>
                                    <strong class="text-teal-800 font-bold">
                                        {{ nextResetInfo.dateStr }} pukul {{ nextResetInfo.timeStr }} ({{ nextResetInfo.remainingStr }})
                                    </strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                        <span class="text-slate-500">Kapasitas Maksimal Server:</span>
                        <span class="font-bold text-slate-700">120 req / menit</span>
                    </div>
                </div>
            </div>

            <!-- Section: Recent API Activity / Gateway Logs for Client -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span>📡 Riwayat Panggilan Gateway Terakhir</span>
                            <span v-if="recentLogs.length" class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-mono font-bold text-slate-700">
                                {{ recentLogs.length }} request
                            </span>
                        </h2>
                        <p class="text-xs text-slate-600">
                            Catatan telemetri 20 request terakhir yang dikirim menggunakan API Key Anda.
                            <span class="md:hidden text-slate-400 block mt-0.5 text-[10px]">(Geser tabel ke samping untuk melihat detail URL & Status →)</span>
                        </p>
                    </div>

                    <a
                        :href="postmanUrl || '/downloads/postman-collection'"
                        download="simrs-gateway-bpjs-postman-collection.json"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-900 hover:bg-amber-100 transition shadow-2xs self-start sm:self-auto"
                    >
                        <span class="flex h-4 w-4 items-center justify-center rounded bg-amber-500 text-white font-bold text-[9px]">P</span>
                        <span>Unduh Postman (.json)</span>
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                    </a>
                </div>

                <!-- Empty State -->
                <div v-if="recentLogs.length === 0" class="rounded-xl border border-dashed border-slate-300 p-8 text-center space-y-2 bg-slate-50/50">
                    <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-500 text-lg">
                        📡
                    </div>
                    <p class="text-xs font-bold text-slate-800">Belum Ada Panggilan API Terdeteksi</p>
                    <p class="text-[11px] text-slate-500">Mulai kirim request menggunakan cURL atau Postman dengan Bearer Token Anda. Setiap pemanggilan akan tercatat otomatis di sini.</p>
                </div>

                <!-- Logs Table -->
                <div v-else class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-600 border-b border-slate-200 font-semibold">
                            <tr>
                                <th class="p-3 whitespace-nowrap">Waktu</th>
                                <th class="p-3">Method & Endpoint</th>
                                <th class="p-3">Layanan</th>
                                <th class="p-3">Status</th>
                                <th class="p-3 text-right">Latensi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-mono">
                            <tr v-for="log in recentLogs" :key="log.id" class="hover:bg-slate-50/70 transition">
                                <td class="p-3 whitespace-nowrap text-slate-600 text-[11px] font-sans">
                                    <div class="font-bold text-slate-800">{{ log.created_at }}</div>
                                    <div class="text-[10px] text-slate-400">{{ log.created_at_human }}</div>
                                </td>
                                <td class="p-3 font-mono">
                                    <div class="flex items-center gap-1.5">
                                        <span
                                            class="rounded px-1.5 py-0.5 text-[10px] font-bold border"
                                            :class="log.method === 'GET' ? 'bg-sky-50 text-sky-700 border-sky-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200'"
                                        >
                                            {{ log.method }}
                                        </span>
                                        <span class="text-slate-900 text-xs font-medium truncate max-w-sm" :title="log.endpoint">
                                            {{ log.endpoint }}
                                        </span>
                                    </div>
                                </td>
                                <td class="p-3 whitespace-nowrap font-sans">
                                    <span class="rounded bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-700">
                                        {{ log.service || 'BPJS Gateway' }}
                                    </span>
                                </td>
                                <td class="p-3 whitespace-nowrap font-sans">
                                    <span
                                        class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-bold"
                                        :class="log.status_code >= 200 && log.status_code < 300 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : (log.status_code === 429 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-rose-50 text-rose-700 border-rose-200')"
                                    >
                                        {{ log.status_code }} {{ log.status_code === 200 ? 'OK' : (log.status_code === 429 ? 'Limit Tercapai' : 'Error') }}
                                    </span>
                                </td>
                                <td class="p-3 text-right whitespace-nowrap font-mono text-slate-700 font-medium">
                                    {{ log.response_time_ms }} ms
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Middle Section: Payment Proofs & Activation History -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span>💳 Bukti Pembayaran & Riwayat Aktivasi</span>
                            <span v-if="paymentProofs.length" class="rounded-full bg-teal-100 px-2 py-0.5 text-xs font-bold text-teal-800">
                                {{ paymentProofs.length }}
                            </span>
                        </h2>
                        <p class="text-xs text-slate-600">
                            Unggah bukti transfer untuk mengaktifkan lisensi bulanan tanpa batas kuota harian.
                            <span class="md:hidden text-slate-400 block mt-0.5 text-[10px]">(Geser tabel ke samping untuk melihat detail bukti & status →)</span>
                        </p>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-teal-800 px-3.5 py-2 text-xs font-bold text-white hover:bg-teal-900 transition shadow-2xs self-start sm:self-auto"
                        @click="showUploadModal = true"
                    >
                        <span>+ Upload Bukti Transfer</span>
                    </button>
                </div>

                <!-- Empty State -->
                <div v-if="paymentProofs.length === 0" class="rounded-xl border border-dashed border-slate-300 p-8 text-center space-y-3 bg-slate-50/50">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-teal-50 text-teal-700 text-xl">
                        💳
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-800">Belum Ada Bukti Pembayaran Diunggah</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">Setelah transfer, unggah foto/screenshot bukti transfer di sini untuk aktivasi instan.</p>
                    </div>
                    <div class="flex justify-center gap-2 pt-1">
                        <button
                            type="button"
                            class="rounded-lg bg-teal-800 px-3 py-1.5 text-xs font-bold text-white hover:bg-teal-900 transition"
                            @click="showUploadModal = true"
                        >
                            Upload Bukti Transfer Sekarang
                        </button>
                        <a
                            :href="telegramChatUrl"
                            target="_blank"
                            class="rounded-lg bg-sky-500 px-3 py-1.5 text-xs font-bold text-white hover:bg-sky-600 transition"
                        >
                            Chat Telegram @{{ telegramHandle }}
                        </a>
                    </div>
                </div>

                <!-- Payment Proofs Table -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs border border-slate-200 rounded-xl overflow-hidden">
                        <thead class="bg-slate-50 text-slate-600 border-b border-slate-200">
                            <tr>
                                <th class="p-3 font-semibold">Paket & Nominal</th>
                                <th class="p-3 font-semibold">Bukti Gambar</th>
                                <th class="p-3 font-semibold">Bank / Catatan</th>
                                <th class="p-3 font-semibold">Waktu Unggah</th>
                                <th class="p-3 font-semibold">Status</th>
                                <th class="p-3 font-semibold text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="item in paymentProofs" :key="item.id" class="hover:bg-slate-50/70 transition">
                                <td class="p-3">
                                    <div class="font-bold text-slate-900">{{ item.plan_name }}</div>
                                    <div class="font-mono text-teal-800 font-semibold text-[11px]">{{ formatRupiah(item.amount) }}</div>
                                </td>
                                <td class="p-3">
                                    <button
                                        v-if="item.proof_url"
                                        type="button"
                                        class="group relative block h-12 w-16 overflow-hidden rounded-lg border border-slate-300 bg-slate-100 hover:ring-2 hover:ring-teal-500 transition"
                                        @click="selectedProofImageModal = item.proof_url"
                                        title="Klik untuk memperbesar gambar"
                                    >
                                        <img :src="item.proof_url" :alt="item.original_filename" class="h-full w-full object-cover group-hover:scale-105 transition" />
                                        <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-[10px] font-bold transition">
                                            🔍 Zoom
                                        </div>
                                    </button>
                                    <span v-else class="text-slate-400 font-mono text-[10px]">-</span>
                                </td>
                                <td class="p-3 text-slate-600">
                                    <div v-if="item.bank_source" class="font-semibold text-slate-800">{{ item.bank_source }}</div>
                                    <div v-if="item.notes" class="text-[11px] text-slate-500 italic">{{ item.notes }}</div>
                                    <div v-if="item.admin_notes" class="text-[10px] text-amber-700 bg-amber-50 rounded p-1 mt-1 border border-amber-200">
                                        Catatan Admin: {{ item.admin_notes }}
                                    </div>
                                </td>
                                <td class="p-3 text-slate-500 text-[11px] whitespace-nowrap">
                                    <div>{{ item.created_at }}</div>
                                    <div v-if="item.verified_at" class="text-[10px] text-emerald-600 font-medium">Diverifikasi: {{ item.verified_at }}</div>
                                </td>
                                <td class="p-3 whitespace-nowrap">
                                    <span
                                        v-if="item.status === 'approved'"
                                        class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700 border border-emerald-200"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Disetujui & Aktif
                                    </span>
                                    <span
                                        v-else-if="item.status === 'rejected'"
                                        class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-bold text-rose-700 border border-rose-200"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                        Ditolak
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-700 border border-amber-200"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        Menunggu Verifikasi
                                    </span>
                                </td>
                                <td class="p-3 text-right whitespace-nowrap">
                                    <a
                                        :href="telegramChatUrl"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 rounded bg-sky-50 px-2 py-1 text-[11px] font-bold text-sky-700 hover:bg-sky-100 transition border border-sky-200"
                                    >
                                        <span>Konfirmasi Telegram</span>
                                        <span>→</span>
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Bottom Section: Quickstart Code Playground -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Panduan Integrasi Langsung (Quickstart)</h2>
                        <p class="text-xs text-slate-600">Salin dan jalankan kode di bawah ini pada sistem SIMRS Anda. Token Anda sudah terisi otomatis.</p>
                    </div>

                    <!-- Language Switcher Tabs -->
                    <div class="flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 p-1 text-xs font-semibold overflow-x-auto max-w-full">
                        <button
                            type="button"
                            v-for="lang in ['curl', 'php', 'js', 'python']"
                            :key="lang"
                            class="rounded px-2.5 py-1 uppercase text-[11px] transition whitespace-nowrap"
                            :class="activeCodeTab === lang ? 'bg-teal-800 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            @click="activeCodeTab = lang"
                        >
                            {{ lang }}
                        </button>
                    </div>
                </div>

                <!-- Code Container -->
                <div class="relative rounded-xl border border-slate-800 bg-slate-950 p-4 overflow-hidden">
                    <button
                        type="button"
                        class="absolute top-3 right-3 rounded bg-slate-800 px-2.5 py-1 text-[11px] font-semibold text-slate-300 hover:bg-slate-700 transition"
                        @click="copyToClipboard(codeSnippets[activeCodeTab], 'snippet')"
                    >
                        {{ copiedField === 'snippet' ? '✓ Tersalin' : 'Salin Kode' }}
                    </button>
                    <pre class="overflow-x-auto text-xs text-emerald-300 font-mono leading-relaxed pt-2">{{ codeSnippets[activeCodeTab] }}</pre>
                </div>
            </div>
        </main>

        <!-- MODAL 1: Upload Bukti Transfer Modal (Strict Image Only) -->
        <div v-if="showUploadModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs overflow-y-auto">
            <div class="relative w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl space-y-4 my-8">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-100 text-teal-800 font-bold">
                            💳
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Upload Bukti Transfer Pembayaran</h3>
                            <p class="text-[11px] text-slate-500">Konfirmasi pembayaran aktivasi lisensi SIMRS Gateway</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="text-slate-400 hover:text-slate-600 font-bold text-lg leading-none"
                        @click="showUploadModal = false"
                    >
                        &times;
                    </button>
                </div>

                <!-- Payment Methods Card: QRIS or Bank Transfer -->
                <div class="rounded-xl border border-teal-200 bg-teal-50/60 p-3.5 text-xs text-teal-950 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-teal-900">Pilih Metode Pembayaran:</span>
                        <div class="flex rounded-lg border border-teal-300 bg-white p-0.5 text-[11px] font-bold">
                            <button
                                type="button"
                                class="rounded px-2.5 py-1 transition cursor-pointer"
                                :class="paymentMethodTab === 'qris' ? 'bg-teal-700 text-white shadow-xs' : 'text-teal-900 hover:bg-teal-50'"
                                @click="paymentMethodTab = 'qris'"
                            >
                                📱 QRIS (Instan)
                            </button>
                            <button
                                type="button"
                                class="rounded px-2.5 py-1 transition cursor-pointer"
                                :class="paymentMethodTab === 'bank' ? 'bg-teal-700 text-white shadow-xs' : 'text-teal-900 hover:bg-teal-50'"
                                @click="paymentMethodTab = 'bank'"
                            >
                                🏦 Transfer Bank
                            </button>
                        </div>
                    </div>

                    <!-- TAB QRIS -->
                    <div v-show="paymentMethodTab === 'qris'" class="space-y-2.5 bg-white rounded-xl p-3 border border-teal-200/80">
                        <div class="flex items-center justify-between text-[11px] font-bold text-slate-800">
                            <span class="flex items-center gap-1.5">
                                <span class="rounded bg-sky-100 text-sky-800 px-1.5 py-0.5 text-[10px]">QRIS Nasional</span>
                                <span>BCA, Mandiri, BRI, BNI, GoPay, OVO, Dana, ShopeePay</span>
                            </span>
                        </div>
                        <div class="flex flex-col sm:flex-row items-center gap-3">
                            <div class="relative group shrink-0">
                                <img
                                    :src="settings.payment_qris_image || '/images/qris.jpeg'"
                                    alt="QRIS Pembayaran SIMRS Gateway"
                                    class="h-36 w-36 rounded-lg border border-slate-200 object-contain p-1 bg-white shadow-xs cursor-pointer hover:opacity-95 transition"
                                    @click="showQrisModal = true"
                                />
                                <span class="absolute inset-0 flex items-center justify-center bg-black/40 text-white text-[10px] font-bold rounded-lg opacity-0 group-hover:opacity-100 transition pointer-events-none">
                                    🔍 Klik Perbesar
                                </span>
                            </div>
                            <div class="text-[11px] text-slate-600 space-y-1">
                                <div class="font-bold text-slate-900 text-xs">Cara Bayar via QRIS:</div>
                                <ol class="list-decimal list-inside space-y-0.5 text-[11px] text-slate-600">
                                    <li>Buka m-Banking atau E-Wallet apa saja.</li>
                                    <li>Pilih menu <strong>Scan / Bayar QRIS</strong>.</li>
                                    <li>Scan QR di samping, masukkan nominal paket.</li>
                                    <li>Simpan bukti transfer dan upload di form bawah.</li>
                                </ol>
                                <div class="pt-1">
                                    <a
                                        :href="settings.payment_qris_image || '/images/qris.jpeg'"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 text-[10px] font-bold text-teal-700 hover:text-teal-800 hover:underline"
                                    >
                                        <span>Buka Gambar QRIS Penuh ↗</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB TRANSFER BANK -->
                    <div v-show="paymentMethodTab === 'bank'" class="space-y-2">
                        <!-- Rekening 1 (Utama) -->
                        <div class="flex items-center justify-between font-mono bg-white rounded-lg p-2.5 border border-teal-200/80 text-xs">
                            <div>
                                <div class="text-[10px] text-slate-400 font-sans uppercase font-bold">{{ settings.payment_bank_name || 'Bank BCA' }}</div>
                                <div class="font-bold text-slate-900 text-sm tracking-wide">{{ settings.payment_account_number || '8831-2940-11' }}</div>
                                <div class="text-[11px] text-slate-600 font-sans">a.n. <strong>{{ settings.payment_account_holder || 'Ahmad Fauzi Rahman' }}</strong></div>
                            </div>
                            <button
                                type="button"
                                class="rounded bg-teal-100 px-2.5 py-1 text-[11px] font-bold text-teal-800 hover:bg-teal-200 transition"
                                @click="copyToClipboard((settings.payment_account_number || '8831294011').replace(/[^0-9]/g, ''), 'rek_1')"
                            >
                                {{ copiedField === 'rek_1' ? '✓ Tersalin' : 'Salin Rekening' }}
                            </button>
                        </div>

                        <!-- Rekening 2 (Jika diisi) -->
                        <div v-if="settings.payment_account_number_2" class="flex items-center justify-between font-mono bg-white rounded-lg p-2.5 border border-teal-200/80 text-xs">
                            <div>
                                <div class="text-[10px] text-slate-400 font-sans uppercase font-bold">{{ settings.payment_bank_name_2 || 'Bank Rekening 2' }}</div>
                                <div class="font-bold text-slate-900 text-sm tracking-wide">{{ settings.payment_account_number_2 }}</div>
                                <div class="text-[11px] text-slate-600 font-sans">a.n. <strong>{{ settings.payment_account_holder_2 }}</strong></div>
                            </div>
                            <button
                                type="button"
                                class="rounded bg-teal-100 px-2.5 py-1 text-[11px] font-bold text-teal-800 hover:bg-teal-200 transition"
                                @click="copyToClipboard(settings.payment_account_number_2.replace(/[^0-9]/g, ''), 'rek_2')"
                            >
                                {{ copiedField === 'rek_2' ? '✓ Tersalin' : 'Salin Rekening' }}
                            </button>
                        </div>
                    </div>

                    <!-- Nominal Helper -->
                    <div class="flex items-center justify-between text-[11px] text-slate-600 pt-0.5 border-t border-teal-200/60">
                        <span>Nominal Paket Bulanan:</span>
                        <strong class="text-teal-900 font-bold font-mono">{{ formatRupiah(settings.pricing_monthly_price ?? 450000) }}</strong>
                    </div>
                </div>

                <form @submit.prevent="submitPaymentProof" class="space-y-3.5 text-xs">
                    <!-- Error Notification -->
                    <div v-if="uploadError || proofForm.errors.proof_image" class="rounded-lg bg-rose-50 border border-rose-200 p-2.5 text-rose-800 text-xs flex items-start gap-2">
                        <span class="text-rose-600 font-bold shrink-0">⚠️</span>
                        <span>{{ uploadError || proofForm.errors.proof_image }}</span>
                    </div>

                    <!-- Plan Selection -->
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Paket Lisensi yang Dibeli</label>
                        <select
                            v-model="proofForm.plan_name"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-800 outline-none focus:border-teal-600"
                        >
                            <option value="Lisensi Bulanan (SaaS) - Akses Semaunya">Lisensi Bulanan (SaaS) - Akses Semaunya ({{ formatRupiah(settings.pricing_monthly_price ?? 450000) }})</option>
                            <option value="Single Hospital - Full Source Code">Single Hospital - Full Source Code ({{ formatRupiah(settings.pricing_hospital_price ?? 4500000) }})</option>
                        </select>
                    </div>

                    <!-- Nominal Input -->
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Nominal yang Ditransfer (Rp)</label>
                        <input
                            v-model.number="proofForm.amount"
                            type="number"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-mono font-bold text-slate-900 outline-none focus:border-teal-600"
                            placeholder="450000"
                        />
                    </div>

                    <!-- FILE INPUT (STRICT IMAGE ONLY) -->
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">
                            Foto / Screenshot Bukti Transfer
                            <span class="text-rose-500 font-bold">* (Khusus File Gambar)</span>
                        </label>
                        <div class="rounded-xl border-2 border-dashed border-slate-300 p-3.5 text-center bg-slate-50 hover:bg-slate-100/60 transition">
                            <input
                                ref="fileInputRef"
                                type="file"
                                accept="image/png, image/jpeg, image/jpg, image/webp"
                                @change="onFileSelected"
                                class="hidden"
                                id="proof-upload-input"
                            />
                            <label for="proof-upload-input" class="cursor-pointer block space-y-1.5">
                                <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-teal-100 text-teal-800 font-bold text-base">
                                    📷
                                </div>
                                <div class="text-xs font-bold text-teal-800 hover:underline">
                                    Klik di sini untuk memilih foto bukti transfer
                                </div>
                                <p class="text-[10px] text-slate-500">
                                    Format diizinkan: <strong>JPG, JPEG, PNG, WEBP</strong> (Maksimal 5 MB).
                                    <span class="text-rose-600 font-semibold block mt-0.5">Dokumen non-gambar (PDF, ZIP, DOC) dilarang.</span>
                                </p>
                            </label>

                            <!-- Live Image Preview -->
                            <div v-if="previewImage" class="mt-3 pt-3 border-t border-slate-200 text-left">
                                <div class="flex items-center justify-between text-[11px] font-bold text-slate-700 mb-1.5">
                                    <span>Pratinjau Gambar yang Dipilih:</span>
                                    <button
                                        type="button"
                                        class="text-rose-600 hover:text-rose-800 font-bold text-xs"
                                        @click="previewImage = null; proofForm.proof_image = null; if (fileInputRef) fileInputRef.value = '';"
                                    >
                                        Hapus Foto
                                    </button>
                                </div>
                                <div class="relative max-h-48 overflow-hidden rounded-lg border border-slate-300 bg-white">
                                    <img :src="previewImage" alt="Preview Bukti Transfer" class="w-full h-auto object-contain max-h-48" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bank Source & Notes -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Bank Pengirim / Rekening Anda</label>
                            <input
                                v-model="proofForm.bank_source"
                                type="text"
                                placeholder="Contoh: BCA an Budi"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-900 outline-none focus:border-teal-600"
                            />
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Catatan Tambahan (Opsional)</label>
                            <input
                                v-model="proofForm.notes"
                                type="text"
                                placeholder="Contoh: Pembayaran lisensi bulan April"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-900 outline-none focus:border-teal-600"
                            />
                        </div>
                    </div>

                    <!-- Direct Telegram Helper -->
                    <div class="rounded-lg bg-sky-50 p-2.5 border border-sky-200 text-[11px] text-sky-900 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-1.5">
                            <svg class="h-4 w-4 fill-sky-600 shrink-0" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                            </svg>
                            <span>Setelah kirim bukti, Anda dapat konfirmasi instan ke Telegram:</span>
                        </div>
                        <a
                            :href="telegramChatUrl"
                            target="_blank"
                            class="font-bold text-sky-700 underline shrink-0"
                        >
                            @{{ telegramHandle }}
                        </a>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-slate-100">
                        <button
                            type="button"
                            class="rounded-xl border border-slate-300 px-4 py-2 font-semibold text-slate-700 hover:bg-slate-100 transition"
                            @click="showUploadModal = false"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-teal-800 px-4 py-2 font-bold text-white hover:bg-teal-900 transition disabled:opacity-50"
                            :disabled="proofForm.processing"
                        >
                            <svg v-if="proofForm.processing" class="h-3.5 w-3.5 animate-spin text-white" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>{{ proofForm.processing ? 'Mengunggah Gambar...' : 'Kirim Bukti Pembayaran' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL 2: Full Image Lightbox Zoom Preview -->
        <div v-if="selectedProofImageModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm" @click.self="selectedProofImageModal = null">
            <div class="relative max-w-3xl max-h-[90vh] bg-white rounded-2xl overflow-hidden shadow-2xl flex flex-col">
                <div class="flex items-center justify-between p-3 border-b border-slate-100 bg-slate-50 text-xs">
                    <span class="font-bold text-slate-800">Foto Bukti Transfer Pembayaran</span>
                    <button
                        type="button"
                        class="text-slate-500 hover:text-slate-800 font-bold text-lg leading-none"
                        @click="selectedProofImageModal = null"
                    >
                        &times;
                    </button>
                </div>
                <div class="p-4 flex-1 overflow-auto flex items-center justify-center bg-slate-900">
                    <img :src="selectedProofImageModal" alt="Bukti Transfer Full" class="max-h-[75vh] w-auto object-contain rounded" />
                </div>
                <div class="p-3 border-t border-slate-100 text-right text-xs">
                    <a :href="selectedProofImageModal" target="_blank" download class="font-bold text-teal-700 hover:underline">
                        Unduh Foto Asli ↗
                    </a>
                </div>
            </div>
        </div>

        <!-- MODAL 3: QRIS Full View / Zoom Modal -->
        <div
            v-if="showQrisModal"
            class="fixed inset-0 z-60 flex items-center justify-center bg-slate-900/80 p-4 backdrop-blur-xs"
            @click="showQrisModal = false"
        >
            <div class="relative max-w-sm w-full rounded-2xl bg-white p-5 shadow-2xl text-center space-y-3" @click.stop>
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <div class="text-left">
                        <span class="font-bold text-xs text-slate-900 block">QRIS Pembayaran SIMRS Gateway</span>
                        <span class="text-[10px] text-slate-500">Scan via m-Banking atau E-Wallet</span>
                    </div>
                    <button type="button" class="text-slate-400 hover:text-slate-600 font-bold text-lg leading-none" @click="showQrisModal = false">
                        &times;
                    </button>
                </div>
                <div class="p-2 bg-slate-50 rounded-xl border border-slate-200 inline-block">
                    <img
                        :src="settings.payment_qris_image || '/images/qris.jpeg'"
                        alt="QRIS Pembayaran SIMRS Gateway"
                        class="w-64 h-64 object-contain mx-auto rounded-lg bg-white p-1"
                    />
                </div>
                <div class="space-y-1">
                    <p class="text-[11px] font-semibold text-slate-700">Mendukung BCA, Mandiri, BRI, BNI, GoPay, OVO, Dana, ShopeePay</p>
                    <a
                        :href="settings.payment_qris_image || '/images/qris.jpeg'"
                        target="_blank"
                        download
                        class="inline-block rounded-lg bg-teal-800 text-white px-3.5 py-1.5 text-xs font-bold hover:bg-teal-900 transition mt-2 shadow-xs"
                    >
                        📥 Download QRIS (Gambar)
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
