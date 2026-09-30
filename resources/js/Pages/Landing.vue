<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    whatsappNumber: {
        type: String,
        default: '6281234567890',
    },
    supportEmail: {
        type: String,
        default: 'support@simrsgateway.id',
    },
    user: {
        type: Object,
        default: null,
    },
});

const page = usePage();
const settings = computed(() => page.props.site_settings ?? {});

const activeCodeTab = ref('php');
const activeModuleTab = ref('vclaim');
const couponCopied = ref(false);
const isMobileMenuOpen = ref(false);

const navLinks = [
    { label: 'Beranda', href: '#', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { label: 'Arsitektur', href: '#arsitektur', icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10' },
    { label: 'Katalog API', href: '#katalog', icon: 'M4 6h16M4 10h16M4 14h16M4 18h16', count: '209' },
    { label: 'Integrasi', href: '#kode', icon: 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4' },
    { label: 'Lisensi', href: '#lisensi', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
    { label: 'FAQ', href: '#faq', icon: 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
];

const closeMobileMenu = () => {
    isMobileMenuOpen.value = false;
};

const formatRupiah = (num) => {
    if (num === null || num === undefined) return 'Rp 0';
    return 'Rp ' + Number(num).toLocaleString('id-ID');
};

const copyCoupon = async (code) => {
    try {
        await navigator.clipboard.writeText(code);
        couponCopied.value = true;
        setTimeout(() => couponCopied.value = false, 2000);
    } catch {
        // fallback
    }
};

const waGeneralUrl = computed(() => {
    const wa = settings.value.whatsapp_contact || props.whatsappNumber;
    const text = encodeURIComponent('Halo Tim SIMRS Gateway, saya ingin konsultasi mengenai implementasi Bridging BPJS & demo simulator.');
    return `https://wa.me/${wa}?text=${text}`;
});

const waMonthlyUrl = computed(() => {
    const wa = settings.value.whatsapp_contact || props.whatsappNumber;
    const priceStr = formatRupiah(settings.value.pricing_monthly_price ?? 450000);
    const promoCode = settings.value.pricing_monthly_promo_code ? ` (Kode Promo: ${settings.value.pricing_monthly_promo_code})` : '';
    const text = encodeURIComponent(`Halo Tim SIMRS Gateway, saya ingin berlangganan lisensi akses bulanan SIMRS BPJS Studio (${priceStr} / bulan)${promoCode}. Mohon info aktivasi akun.`);
    return `https://wa.me/${wa}?text=${text}`;
});

const waHospitalUrl = computed(() => {
    const wa = settings.value.whatsapp_contact || props.whatsappNumber;
    const priceStr = formatRupiah(settings.value.pricing_hospital_price ?? 4500000);
    const text = encodeURIComponent(`Halo Tim SIMRS Gateway, saya tertarik membeli Full Source Code Paket Lisensi Rumah Sakit (Single Hospital License - ${priceStr}). Mohon info prosedur dan invoice.`);
    return `https://wa.me/${wa}?text=${text}`;
});

const telegramHandle = computed(() => {
    return (settings.value.telegram_contact || 'FauziRahman95').replace('@', '');
});

const telegramGeneralUrl = computed(() => {
    const handle = telegramHandle.value;
    const text = encodeURIComponent(`Halo mas @${handle}, saya ingin konsultasi mengenai implementasi Bridging BPJS & lisensi SIMRS Gateway.`);
    return `https://t.me/${handle}?text=${text}`;
});

const telegramMonthlyUrl = computed(() => {
    const handle = telegramHandle.value;
    const priceStr = formatRupiah(settings.value.pricing_monthly_price ?? 450000);
    const promoCode = settings.value.pricing_monthly_promo_code ? ` (Kode Promo: ${settings.value.pricing_monthly_promo_code})` : '';
    const text = encodeURIComponent(`Halo mas @${handle}, saya ingin berlangganan lisensi bulanan SIMRS BPJS (${priceStr} / bulan)${promoCode}. Mohon info aktivasi.`);
    return `https://t.me/${handle}?text=${text}`;
});

const telegramHospitalUrl = computed(() => {
    const handle = telegramHandle.value;
    const priceStr = formatRupiah(settings.value.pricing_hospital_price ?? 4500000);
    const text = encodeURIComponent(`Halo mas @${handle}, saya tertarik membeli Full Source Code Paket Rumah Sakit (${priceStr}). Mohon info prosedur dan invoice.`);
    return `https://t.me/${handle}?text=${text}`;
});

const codeSnippets = {
    php: `// Integrasi SIMRS Lokal ke Gateway BPJS (Cukup 4 Baris):
$response = Http::get('http://gateway-rs.lan/api/v1/bpjs/peserta', [
    'nomor' => '0001234567890',
    'tipe'  => '1', // 1: No Kartu BPJS, 2: NIK KTP
]);

$peserta = $response->json('response.peserta');
// Gateway otomatis mengurus HMAC-SHA256, Enkripsi AES-256, & Dekripsi LZ-String!`,

    curl: `# Request dari Terminal atau Server Intranet:
curl -X GET "http://gateway-rs.lan/api/v1/bpjs/peserta?nomor=0001234567890&tipe=1" \\
  -H "Accept: application/json" \\
  -H "X-Client-Id: RS-SEHAT-01"`,

    js: `// Node.js / Express / Frontend Integration:
const res = await axios.get('http://gateway-rs.lan/api/v1/bpjs/peserta', {
  params: { nomor: '0001234567890', tipe: '1' }
});

console.log(res.data.response.peserta.nama); 
// Output: SUTRISNO WIJAYA (Langsung decrypted plain JSON)`,

    python: `# Python / Django / FastAPI Integration:
import requests

res = requests.get('http://gateway-rs.lan/api/v1/bpjs/peserta', params={
    'nomor': '0001234567890',
    'tipe': '1'
})
data = res.json()['response']['peserta']`
};

const moduleList = [
    {
        id: 'vclaim',
        name: 'VClaim v3.0',
        count: '110 Endpoint',
        badge: 'Rawat Jalan & Rawat Inap',
        highlights: ['Penerbitan & Update SEP', 'Rujukan Online FKTP & Antar RS', 'Surat Kontrol & SPRI DPJP', 'Monitoring Klaim & Riwayat Pelayanan', 'Referensi Poli, Faskes & Diagnosa ICD-10']
    },
    {
        id: 'antreanrs',
        name: 'Antrean RS',
        count: '18 Endpoint',
        badge: 'Mobile JKN Ready',
        highlights: ['Sinkronisasi Jadwal Dokter Poli', 'Pemesanan Tiket Antrean Online', 'Check-in Kode Booking Pasien', 'Pembatalan Antrean Otomatis', 'Monitoring Kuota & Estimasi Pelayanan']
    },
    {
        id: 'apotek',
        name: 'Apotek BPJS',
        count: '18 Endpoint',
        badge: 'Farmasi & Obat Kronis',
        highlights: ['Pelayanan Resep Elektronik', 'Klaim Obat Kronis / Program Rujuk Balik (PRB)', 'Pencarian Master DPHO BPJS', 'Daftar Obat Kemoterapi & Khusus']
    },
    {
        id: 'pcare',
        name: 'PCare',
        count: '51 Endpoint',
        badge: 'Faskes 1 / Klinik Pratama',
        highlights: ['Pendaftaran Kunjungan Faskes Primer', 'Input Diagnosa & Tindakan Medis', 'Rujukan Vertikal/Horizontal', 'Pemberian Obat FKTP & Pemeriksaan Lab']
    },
    {
        id: 'aplicares',
        name: 'Aplicares',
        count: '5 Endpoint',
        badge: 'Ketersediaan TT',
        highlights: ['Update Real-time Kapasitas Tempat Tidur', 'Ketersediaan Kamar ICU, Isolasi & Rawat Inap', 'Katalog Kelas Perawatan BPJS']
    },
    {
        id: 'icare',
        name: 'i-Care JKN',
        count: '2 Endpoint',
        badge: 'Medical Record Sharing',
        highlights: ['Akses Riwayat Pelayanan Pasien 1 Tahun', 'Integrasi Rekam Medis Terpadu Antar-Faskes']
    },
    {
        id: 'antreanfktp',
        name: 'Antrean FKTP',
        count: '5 Endpoint',
        badge: 'Klinik & Puskesmas',
        highlights: ['Ambil Antrean Faskes Tingkat Pertama', 'Integrasi Nomor Antrean Poli Klinik ke Mobile JKN']
    },
];

const faqs = [
    {
        q: 'Apakah sistem ini sesuai dengan regulasi PMK 24 / 2022 tentang Rekam Medis?',
        a: 'Sangat sesuai. Dengan opsi lisensi On-Premise (Beli Putus Source Code), gateway diinstal di server intranet lokal rumah sakit Anda sendiri. Tidak ada data rekam medis pasien yang dikirim ke cloud pihak ketiga, menjamin kepatuhan penuh terhadap UU PDP dan PMK 24.',
    },
    {
        q: 'Bagaimana cara gateway menangani enkripsi AES-256 dan Signature BPJS?',
        a: 'SIMRS Gateway memiliki modul cipher bawaan yang secara otomatis menghitung HMAC-SHA256 timestamp signature, mengenkripsi payload request sebelum dikirim ke BPJS, dan mendekripsi balasan LZ-string secara instan. Aplikasi SIMRS Anda hanya perlu mengirim dan menerima data dalam format JSON standar.',
    },
    {
        q: 'Apakah bisa digunakan untuk persiapan UAT (User Acceptance Test) BPJS Cabang?',
        a: 'Ya. Salah satu fungsi utama gateway ini adalah sebagai simulator pengujian. Seluruh 209 endpoint telah dilengkapi dengan mock response dummy yang terstruktur rapi sesuai standar katalog BPJS Kesehatan, sehingga tim IT Anda siap melakukan demonstrasi UAT di hadapan tim IT Kantor Cabang BPJS.',
    },
    {
        q: 'Teknologi apa yang digunakan dalam source code SIMRS Gateway?',
        a: 'Dibangun menggunakan stack enterprise modern: PHP 8.2+ / Laravel 12 pada sisi backend, dan Vue 3 Inertia + Tailwind CSS pada sisi frontend. Sangat ringan, terstruktur rapi, tanpa bloatware, dan mudah dihubungkan ke database SIMRS existing (MySQL, PostgreSQL, atau SQL Server).',
    },
];

const openFaq = ref(0);
const toggleFaq = (idx) => {
    openFaq.value = openFaq.value === idx ? -1 : idx;
};
</script>

<template>
    <!-- Meta description, keywords & Open Graph ada di app.blade.php (sekali untuk seluruh situs). -->
    <Head title="SIMRS Gateway - Infrastruktur Bridging BPJS Kesehatan & SATUSEHAT" />

    <div class="min-h-screen bg-slate-50 text-slate-900 font-sans selection:bg-teal-700 selection:text-white antialiased">
        <!-- Global Promo Announcement Bar -->
        <div
            v-if="settings.promo_banner_active"
            class="bg-gradient-to-r from-teal-950 via-teal-900 to-emerald-950 px-4 py-2 text-white text-xs font-semibold shadow-inner border-b border-teal-800/60"
        >
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-3">
                <div class="flex items-center gap-2 truncate">
                    <span class="inline-flex h-2 w-2 rounded-full bg-amber-400 animate-ping shrink-0"></span>
                    <span class="truncate text-[11px] sm:text-xs font-medium text-teal-100">{{ settings.promo_banner_text }}</span>
                </div>
                <a
                    :href="settings.promo_banner_link || '#lisensi'"
                    class="shrink-0 rounded-full bg-amber-400 hover:bg-amber-300 text-slate-950 px-3 py-0.5 text-[10px] font-extrabold uppercase tracking-wider transition shadow-xs"
                >
                    Lihat Promo →
                </a>
            </div>
        </div>

        <!-- Top App Bar -->
        <header class="sticky top-0 z-50 border-b border-slate-200/90 bg-white/95 shadow-xs backdrop-blur-md">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8 gap-3 sm:gap-4 flex-nowrap">
                <!-- Brand Info -->
                <Link href="/" class="flex items-center gap-2.5 sm:gap-3 shrink-0 group">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-800 text-white font-bold shadow-xs group-hover:bg-teal-900 transition shrink-0">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div class="shrink-0">
                        <div class="flex items-center gap-1.5 sm:gap-2">
                            <span class="text-sm font-bold tracking-tight text-slate-900 whitespace-nowrap">SIMRS Gateway</span>
                            <span class="rounded bg-teal-50 px-1.5 py-0.5 text-[10px] font-bold text-teal-800 border border-teal-200 whitespace-nowrap">v3.0</span>
                        </div>
                    </div>
                </Link>

                <!-- Navigation Links (Desktop: xl+) Strictly single line / whitespace-nowrap -->
                <nav class="hidden xl:flex items-center gap-1 2xl:gap-2 text-[13px] font-semibold text-slate-600 shrink-0">
                    <a
                        v-for="link in navLinks"
                        :key="link.label"
                        :href="link.href"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-slate-600 hover:text-teal-900 hover:bg-teal-50/70 transition-colors whitespace-nowrap shrink-0"
                    >
                        <span>{{ link.label }}</span>
                        <span
                            v-if="link.count"
                            class="rounded-full bg-teal-100 text-teal-800 px-1.5 py-0.2 text-[10px] font-bold"
                        >
                            {{ link.count }}
                        </span>
                    </a>
                </nav>

                <!-- Action Buttons: Strictly single line / whitespace-nowrap -->
                <div class="flex items-center gap-2 sm:gap-2.5 shrink-0 flex-nowrap">
                    <!-- Telegram Direct Button (Desktop & Tablet) -->
                    <a
                        :href="telegramGeneralUrl"
                        target="_blank"
                        class="hidden md:inline-flex items-center gap-1.5 whitespace-nowrap shrink-0 rounded-full border border-sky-300 bg-sky-50 px-3.5 py-1.5 text-xs font-bold text-sky-800 hover:bg-sky-100 transition shadow-2xs"
                        title="Chat Telegram ke @FauziRahman95"
                    >
                        <svg class="h-3.5 w-3.5 fill-sky-600 shrink-0" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                        </svg>
                        <span>Telegram @{{ telegramHandle }}</span>
                    </a>

                    <!-- Compact Telegram Button (Mobile phone) -->
                    <a
                        :href="telegramGeneralUrl"
                        target="_blank"
                        class="inline-flex md:hidden items-center justify-center h-8 w-8 shrink-0 rounded-lg border border-sky-300 bg-sky-50 text-sky-700 hover:bg-sky-100 transition shadow-2xs"
                        title="Chat Telegram ke @FauziRahman95"
                    >
                        <svg class="h-4 w-4 fill-sky-600" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                        </svg>
                    </a>

                    <template v-if="user">
                        <Link
                            v-if="user.is_superadmin"
                            href="/admin/dashboard"
                            class="inline-flex items-center gap-1.5 whitespace-nowrap shrink-0 rounded-lg bg-slate-900 px-3 sm:px-3.5 py-1.5 text-xs font-bold text-white shadow-xs hover:bg-slate-800 transition"
                        >
                            <svg class="h-3.5 w-3.5 text-teal-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="hidden sm:inline">Admin Panel</span>
                            <span class="sm:hidden">Admin</span>
                        </Link>
                        <template v-else>
                            <Link
                                href="/client/dashboard"
                                class="hidden sm:inline-flex items-center gap-1.5 whitespace-nowrap shrink-0 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition"
                            >
                                <span>Portal Klien</span>
                            </Link>
                            <Link
                                href="/admin/bpjs-tester"
                                class="inline-flex items-center gap-1.5 whitespace-nowrap shrink-0 rounded-lg bg-teal-800 px-3 sm:px-3.5 py-1.5 text-xs font-bold text-white shadow-xs hover:bg-teal-900 transition"
                            >
                                <span>Studio</span>
                                <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </Link>
                        </template>
                    </template>

                    <template v-else>
                        <Link
                            href="/register"
                            class="hidden sm:inline-flex items-center gap-1.5 whitespace-nowrap shrink-0 rounded-lg border border-teal-300 bg-teal-50 px-3.5 py-1.5 text-xs font-bold text-teal-800 shadow-2xs hover:bg-teal-100 transition"
                        >
                            <span>Daftar Klien</span>
                        </Link>
                        <Link
                            href="/login"
                            class="inline-flex items-center gap-1.5 whitespace-nowrap shrink-0 rounded-lg bg-teal-800 px-3.5 py-1.5 text-xs font-bold text-white shadow-xs hover:bg-teal-900 transition"
                        >
                            <span>Masuk</span>
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </Link>
                    </template>

                    <!-- Mobile Hamburger Toggle Button -->
                    <button
                        type="button"
                        class="inline-flex xl:hidden items-center justify-center h-9 w-9 shrink-0 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 hover:text-slate-900 transition shadow-2xs focus:outline-hidden"
                        @click="isMobileMenuOpen = !isMobileMenuOpen"
                        :aria-expanded="isMobileMenuOpen"
                        aria-label="Menu Navigasi Mobile"
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

            <!-- Mobile Navigation Drawer Panel -->
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
                    class="xl:hidden border-b border-slate-200 bg-white/98 shadow-xl backdrop-blur-md px-4 py-4 space-y-4 max-h-[calc(100vh-4.5rem)] overflow-y-auto"
                >
                    <!-- Nav Links List -->
                    <div class="space-y-1">
                        <a
                            v-for="link in navLinks"
                            :key="link.label"
                            :href="link.href"
                            @click="closeMobileMenu"
                            class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-slate-700 hover:bg-teal-50 hover:text-teal-900 transition"
                        >
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-600 shrink-0">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="link.icon" />
                                </svg>
                            </div>
                            <span>{{ link.label }}</span>
                        </a>
                    </div>

                    <!-- Telegram Direct Card in Mobile Drawer -->
                    <div class="rounded-xl border border-sky-200 bg-sky-50/80 p-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-sky-500 text-white shrink-0">
                                    <svg class="h-4 w-4 fill-white" viewBox="0 0 24 24">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-sky-900">Telegram @{{ telegramHandle }}</div>
                                    <div class="text-[10px] text-sky-700">Konsultasi Source Code & Lisensi RS</div>
                                </div>
                            </div>
                            <a
                                :href="telegramGeneralUrl"
                                target="_blank"
                                class="rounded-lg bg-sky-500 hover:bg-sky-600 px-3 py-1.5 text-[11px] font-bold text-white transition shadow-2xs"
                            >
                                Chat →
                            </a>
                        </div>
                    </div>

                    <!-- Auth Actions Block in Mobile Drawer -->
                    <div class="pt-2 border-t border-slate-100 space-y-2">
                        <template v-if="user">
                            <Link
                                v-if="user.is_superadmin"
                                href="/admin/dashboard"
                                @click="closeMobileMenu"
                                class="flex items-center justify-center gap-2 w-full rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold text-white shadow-xs"
                            >
                                <svg class="h-4 w-4 text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span>Masuk ke Dashboard Superadmin</span>
                            </Link>
                            <div v-else class="grid grid-cols-2 gap-2">
                                <Link
                                    href="/client/dashboard"
                                    @click="closeMobileMenu"
                                    class="flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 shadow-2xs hover:bg-slate-50 transition"
                                >
                                    Portal Klien
                                </Link>
                                <Link
                                    href="/admin/bpjs-tester"
                                    @click="closeMobileMenu"
                                    class="flex items-center justify-center rounded-xl bg-teal-800 px-3 py-2 text-xs font-bold text-white shadow-xs hover:bg-teal-900 transition"
                                >
                                    Buka Studio UI
                                </Link>
                            </div>
                        </template>
                        <template v-else>
                            <Link
                                href="/register"
                                @click="closeMobileMenu"
                                class="flex items-center justify-center gap-2 w-full rounded-xl border border-teal-300 bg-teal-50 px-4 py-2.5 text-xs font-bold text-teal-800 shadow-2xs hover:bg-teal-100 transition"
                            >
                                <span>Daftar Klien Baru (Dapatkan API Key)</span>
                            </Link>
                            <Link
                                href="/login"
                                @click="closeMobileMenu"
                                class="flex items-center justify-center gap-2 w-full rounded-xl bg-teal-800 px-4 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-teal-900 transition"
                            >
                                <span>Masuk ke Gateway Console</span>
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </Link>
                        </template>
                    </div>
                </div>
            </transition>
        </header>

        <!-- Hero Section: Clean Architectural Focus -->
        <section class="relative border-b border-slate-200/80 bg-white py-16 lg:py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid items-center gap-12 lg:grid-cols-[1.2fr_0.8fr]">
                    <!-- Left Column: Value Statement & Proof -->
                    <div class="space-y-6 text-left">
                        <!-- Security Badge -->
                        <div class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            <span>AES-256-CBC • HMAC-SHA256 • On-Premise Intranet Ready</span>
                        </div>

                        <h1 class="text-3xl font-extrabold tracking-tight text-slate-950 sm:text-5xl sm:leading-[1.15]">
                            Infrastruktur Bridging BPJS Kesehatan Siap Pakai untuk Rumah Sakit.
                        </h1>

                        <p class="text-sm sm:text-base text-slate-600 leading-relaxed max-w-xl">
                            Hilangkan kerumitan kalkulasi signature timestamp, enkripsi AES-256, dan dekripsi LZ-string. Gateway lokal ini menyediakan <strong>209 endpoint terverifikasi</strong> dan simulator interaktif yang siap dipasang langsung di intranet RS Anda.
                        </p>

                        <!-- Primary CTAs -->
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 pt-2">
                            <Link
                                href="/login"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-teal-800 px-5 py-3 text-xs sm:text-sm font-bold text-white shadow-xs hover:bg-teal-900 transition w-full sm:w-auto"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                <span>Coba Demo Simulator Web</span>
                            </Link>

                            <a
                                :href="telegramHospitalUrl"
                                target="_blank"
                                class="inline-flex items-center justify-center gap-2 rounded-xl border border-sky-300 bg-sky-50 px-5 py-3 text-xs sm:text-sm font-bold text-sky-900 shadow-2xs hover:bg-sky-100 transition w-full sm:w-auto"
                            >
                                <svg class="h-4 w-4 fill-sky-600" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                                </svg>
                                <span>Konsultasi Source Code RS</span>
                            </a>
                        </div>

                        <!-- Technical Specs Bar -->
                        <div class="border-t border-slate-200 pt-5 grid grid-cols-3 gap-4 text-xs font-mono">
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-sans font-bold">Katalog Resmi</span>
                                <span class="text-slate-900 font-bold text-sm">209 Endpoint</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-sans font-bold">Modul BPJS</span>
                                <span class="text-slate-900 font-bold text-sm">7 Modul Aktif</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-sans font-bold">Regulasi</span>
                                <span class="text-teal-800 font-bold text-sm">PMK 24 / PDP</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Live Terminal Preview -->
                    <div class="rounded-xl border border-slate-800 bg-slate-950 shadow-xl overflow-hidden font-mono text-xs">
                        <div class="flex items-center justify-between border-b border-slate-800 bg-slate-900/90 px-3 py-2 text-slate-400">
                            <div class="flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-full bg-rose-500/80"></span>
                                <span class="h-2.5 w-2.5 rounded-full bg-amber-500/80"></span>
                                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500/80"></span>
                                <span class="ml-2 text-[11px] text-slate-300 font-bold">gateway-node:8000</span>
                            </div>
                            <span class="rounded bg-teal-950 px-2 py-0.5 text-[10px] text-teal-400 border border-teal-800">200 OK • 682ms</span>
                        </div>

                        <div class="p-4 space-y-3 text-slate-300">
                            <div class="text-slate-400 text-[11px]">
                                <span class="text-sky-400 font-bold">GET</span> /api/v1/bpjs/peserta?nomor=0001234567890
                            </div>

                            <div class="rounded border border-slate-800 bg-slate-900/60 p-3 text-[11px] space-y-1 text-slate-400">
                                <div><span class="text-teal-400">&gt;</span> X-cons-id: <span class="text-slate-200">29871</span></div>
                                <div><span class="text-teal-400">&gt;</span> X-timestamp: <span class="text-slate-200">1759132800</span></div>
                                <div><span class="text-teal-400">&gt;</span> X-signature: <span class="text-emerald-400">SHA256(ConsID+Secret+Time) ✓</span></div>
                            </div>

                            <pre class="text-emerald-300 text-[11px] leading-relaxed overflow-x-auto">{
  "metaData": {
    "code": "200",
    "message": "OK"
  },
  "response": {
    "peserta": {
      "noKartu": "0001234567890",
      "nama": "SUTRISNO WIJAYA",
      "nik": "3201234567890001",
      "statusPeserta": { "keterangan": "AKTIF" },
      "hakKelas": { "keterangan": "KELAS I" }
    }
  }
}</pre>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section: Interactive Architecture Flow -->
        <section id="arsitektur" class="py-16 bg-slate-50 border-b border-slate-200">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-12">
                <div class="text-center space-y-2">
                    <span class="font-mono text-xs font-bold text-teal-800 uppercase tracking-wider">Topologi Sistem</span>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                        Bagaimana SIMRS Gateway Bekerja?
                    </h2>
                    <p class="mx-auto max-w-2xl text-xs sm:text-sm text-slate-600">
                        Aplikasi SIMRS Anda cukup mengirimkan permintaan JSON standar. Seluruh proses kriptografi rumit BPJS ditangani secara otomatis di dalam gateway intranet Anda.
                    </p>
                </div>

                <!-- 3-Step Flow Diagram -->
                <div class="grid gap-4 lg:grid-cols-3">
                    <!-- Step 1 -->
                    <div class="rounded-xl border border-slate-200 bg-white p-6 space-y-3 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <span class="rounded bg-slate-100 px-2 py-0.5 font-mono text-xs font-bold text-slate-700">01. SIMRS Lokal</span>
                            <span class="text-xs text-slate-400 font-mono">Plain JSON</span>
                        </div>
                        <h3 class="font-bold text-sm text-slate-900">Aplikasi Rumah Sakit Mengirim Data</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            SIMRS mengirimkan payload sederhana (contoh: No SEP atau No Kartu) melalui HTTP REST API standar tanpa perlu memikirkan enkripsi.
                        </p>
                    </div>

                    <!-- Step 2 -->
                    <div class="rounded-xl border-2 border-teal-600 bg-white p-6 space-y-3 shadow-sm relative">
                        <div class="flex items-center justify-between">
                            <span class="rounded bg-teal-100 px-2 py-0.5 font-mono text-xs font-bold text-teal-900">02. Gateway Engine</span>
                            <span class="text-xs text-teal-700 font-bold font-mono">Kriptografi Otomatis</span>
                        </div>
                        <h3 class="font-bold text-sm text-slate-900">Otomasi Enkripsi & Signature</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Gateway menghitung HMAC-SHA256 signature, mencocokkan timestamp, mengenkripsi data dengan AES-256-CBC, lalu meneruskannya ke server BPJS.
                        </p>
                    </div>

                    <!-- Step 3 -->
                    <div class="rounded-xl border border-slate-200 bg-white p-6 space-y-3 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <span class="rounded bg-slate-100 px-2 py-0.5 font-mono text-xs font-bold text-slate-700">03. Response BPJS</span>
                            <span class="text-xs text-emerald-700 font-mono font-bold">Auto-Decrypted</span>
                        </div>
                        <h3 class="font-bold text-sm text-slate-900">Mendekripsi Jawaban BPJS</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Respon terenkripsi LZ-string dari BPJS didekripsi secara transparan dan dikembalikan ke SIMRS Anda sebagai JSON siap pakai.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section: 7 Supported Modules Explorer -->
        <section id="katalog" class="py-16 bg-white border-b border-slate-200">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-10">
                <div class="text-center space-y-2">
                    <span class="font-mono text-xs font-bold text-teal-800 uppercase tracking-wider">Katalog Komprehensif</span>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                        Daftar 7 Modul BPJS Kesehatan (209 Endpoint)
                    </h2>
                    <p class="mx-auto max-w-2xl text-xs sm:text-sm text-slate-600">
                        Setiap modul memiliki skenario pengujian sukses dan gagal yang siap dipresentasikan saat uji coba UAT bersama kantor cabang BPJS.
                    </p>
                </div>

                <!-- Module Grid -->
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="item in moduleList"
                        :key="item.id"
                        class="rounded-xl border border-slate-200 bg-slate-50/50 p-5 space-y-3 hover:border-slate-300 hover:bg-white transition shadow-2xs"
                    >
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-sm text-slate-900">{{ item.name }}</span>
                            <span class="rounded bg-teal-50 px-2 py-0.5 font-mono text-[10px] font-bold text-teal-800 border border-teal-200">
                                {{ item.count }}
                            </span>
                        </div>
                        <span class="inline-block rounded bg-slate-200/70 px-2 py-0.5 text-[10px] font-semibold text-slate-700">
                            {{ item.badge }}
                        </span>
                        <ul class="space-y-1 text-xs text-slate-600 pt-1">
                            <li v-for="(hl, hIdx) in item.highlights" :key="hIdx" class="flex items-start gap-1.5">
                                <span class="text-teal-700 font-bold">•</span>
                                <span>{{ hl }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section: Interactive Code Examples -->
        <section id="kode" class="py-16 bg-slate-50 border-b border-slate-200">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-8">
                <div class="text-center space-y-2">
                    <span class="font-mono text-xs font-bold text-teal-800 uppercase tracking-wider">Implementasi Cepat</span>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                        Sederhanakan Kode Bridging SIMRS Anda
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-600">
                        Bebas digunakan dengan bahasa pemrograman apapun yang berjalan di server rumah sakit Anda.
                    </p>
                </div>

                <div class="rounded-xl border border-slate-800 bg-slate-950 overflow-hidden shadow-xl text-xs font-mono">
                    <!-- Tab Selector -->
                    <div class="flex items-center justify-between border-b border-slate-800 bg-slate-900 px-3 py-1.5">
                        <div class="flex items-center gap-1">
                            <button
                                v-for="lang in ['php', 'curl', 'js', 'python']"
                                :key="lang"
                                type="button"
                                class="rounded px-3 py-1 font-semibold uppercase transition"
                                :class="activeCodeTab === lang
                                    ? 'bg-teal-700 text-white shadow-xs'
                                    : 'text-slate-400 hover:text-slate-200'"
                                @click="activeCodeTab = lang"
                            >
                                {{ lang === 'js' ? 'JavaScript' : lang }}
                            </button>
                        </div>
                        <span class="text-[10px] text-slate-400">REST API Client</span>
                    </div>

                    <!-- Code Box -->
                    <pre class="p-5 text-emerald-300 leading-relaxed overflow-x-auto whitespace-pre">{{ codeSnippets[activeCodeTab] }}</pre>
                </div>
            </div>
        </section>

        <!-- Section: Licensing & Pricing -->
        <section id="lisensi" class="py-16 bg-white border-b border-slate-200">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-12">
                <div class="text-center space-y-2">
                    <span class="font-mono text-xs font-bold text-teal-800 uppercase tracking-wider">Opsi Investasi</span>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                        Pilihan Lisensi Transparan Tanpa Biaya Tersembunyi
                    </h2>
                    <p class="mx-auto max-w-2xl text-xs sm:text-sm text-slate-600">
                        Tersedia model langganan cloud untuk evaluasi API, serta opsi beli putus source code untuk instalasi intranet rumah sakit.
                    </p>
                </div>

                <div class="grid gap-6 lg:grid-cols-3 max-w-6xl mx-auto items-stretch">
                    <!-- Card 1: Coba Gratis / Trial -->
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 flex flex-col justify-between space-y-6">
                        <div class="space-y-4">
                            <span class="rounded bg-slate-200 px-2 py-0.5 text-[11px] font-bold text-slate-700">
                                {{ settings.pricing_trial_tag || 'Uji Coba Terbatas' }}
                            </span>
                            <h3 class="text-xl font-bold text-slate-900">{{ settings.pricing_trial_title || 'Coba Gratis (Trial)' }}</h3>
                            <p class="text-xs text-slate-600">{{ settings.pricing_trial_desc || 'Untuk developer yang ingin mencoba simulator sebelum berlangganan.' }}</p>
                            <div class="pt-2">
                                <span class="text-3xl font-extrabold text-slate-900">{{ formatRupiah(settings.pricing_trial_price ?? 0) }}</span>
                                <span class="text-xs text-slate-500 font-semibold"> (Gratis)</span>
                            </div>

                            <ul class="space-y-2 text-xs text-slate-700 pt-3 border-t border-slate-200 font-medium">
                                <li
                                    v-for="(feat, fIdx) in (settings.pricing_trial_features || [`⚠️ Limit ${settings.pricing_trial_limit ?? 50} Request / Hari`, '✓ Akses Simulator UI (209 Endpoint)', '✓ Preview Data Mock Realistis', '✓ Tanpa Kartu Kredit / Tanpa Bayar'])"
                                    :key="fIdx"
                                    :class="fIdx === 0 ? 'font-bold text-amber-700' : ''"
                                >
                                    {{ feat }}
                                </li>
                            </ul>
                        </div>

                        <Link
                            href="/register"
                            class="block w-full text-center rounded-lg border border-slate-300 bg-white py-2.5 text-xs font-bold text-slate-800 hover:bg-slate-100 transition shadow-2xs"
                        >
                            {{ settings.pricing_trial_cta_text || `Daftar Uji Coba (${settings.pricing_trial_limit ?? 50} Req / Hari)` }}
                        </Link>
                    </div>

                    <!-- Card 2: Cloud SaaS / Langganan Bulanan (Akses Semaunya) -->
                    <div class="rounded-2xl border-2 border-teal-600 bg-white p-6 flex flex-col justify-between space-y-6 shadow-xl ring-4 ring-teal-50 relative overflow-hidden">
                        <!-- Promo Ribbon -->
                        <div
                            v-if="settings.pricing_monthly_promo_active"
                            class="absolute -top-1 -right-1 bg-gradient-to-r from-rose-500 to-amber-500 text-white font-extrabold text-[10px] px-4 py-1 rounded-bl-xl shadow-xs uppercase tracking-wider"
                        >
                            {{ settings.pricing_monthly_promo_badge || 'PROMO SPESIAL' }}
                        </div>

                        <div class="space-y-4">
                            <span class="rounded bg-teal-100 px-2.5 py-0.5 text-[11px] font-bold text-teal-800">
                                {{ settings.pricing_monthly_tag || 'Akses Semaunya' }}
                            </span>
                            <h3 class="text-xl font-bold text-slate-900">{{ settings.pricing_monthly_title || 'Lisensi Bulanan (SaaS)' }}</h3>
                            <p class="text-xs text-slate-600 leading-relaxed">{{ settings.pricing_monthly_desc || `Akses tanpa batas ${settings.pricing_trial_limit ?? 50} req/hari, uji coba bebas semua modul sepuasnya.` }}</p>

                            <div class="pt-2">
                                <!-- Strike Price if Promo Active -->
                                <div v-if="settings.pricing_monthly_promo_active && settings.pricing_monthly_strike_price" class="flex items-center gap-2 mb-1">
                                    <span class="text-sm font-bold text-rose-500 line-through">{{ formatRupiah(settings.pricing_monthly_strike_price) }}</span>
                                    <span class="rounded bg-rose-50 px-1.5 py-0.2 text-[10px] font-bold text-rose-700 ring-1 ring-inset ring-rose-600/20">
                                        Hemat {{ formatRupiah(settings.pricing_monthly_strike_price - (settings.pricing_monthly_price ?? 450000)) }}
                                    </span>
                                </div>
                                <div class="flex items-baseline gap-1">
                                    <span class="text-3xl font-extrabold text-teal-800">{{ formatRupiah(settings.pricing_monthly_price ?? 450000) }}</span>
                                    <span class="text-xs text-slate-500 font-semibold">{{ settings.pricing_monthly_period || ' / bulan' }}</span>
                                </div>
                            </div>

                            <!-- Clickable Promo Coupon Code -->
                            <div
                                v-if="settings.pricing_monthly_promo_code"
                                class="flex items-center justify-between rounded-lg border border-dashed border-teal-300 bg-teal-50/80 px-3 py-2 text-xs cursor-pointer hover:bg-teal-100/70 transition"
                                @click="copyCoupon(settings.pricing_monthly_promo_code)"
                            >
                                <div class="flex items-center gap-1.5 text-teal-900 font-medium">
                                    <svg class="h-4 w-4 text-teal-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                    <span>Kupon: <strong class="font-mono font-bold">{{ settings.pricing_monthly_promo_code }}</strong></span>
                                </div>
                                <span class="text-[10px] font-bold text-teal-700 underline">
                                    {{ couponCopied ? 'Tersalin!' : 'Salin Kode' }}
                                </span>
                            </div>

                            <ul class="space-y-2 text-xs text-slate-700 pt-3 border-t border-slate-100 font-medium">
                                <li
                                    v-for="(feat, fIdx) in (settings.pricing_monthly_features || ['✓ Unlimited Request (Akses Semaunya)', '✓ Semua 209 Endpoint Simulator & Proxy', '✓ Akun Personal Aktif 30 Hari', '✓ Perpanjangan Fleksibel Setiap Bulan'])"
                                    :key="fIdx"
                                    :class="fIdx === 0 ? 'font-bold text-emerald-700' : ''"
                                >
                                    {{ feat }}
                                </li>
                            </ul>
                        </div>

                        <div>
                            <a
                                :href="telegramMonthlyUrl"
                                target="_blank"
                                class="flex items-center justify-center gap-2 w-full text-center rounded-lg bg-sky-500 hover:bg-sky-600 py-2.5 text-xs font-bold text-white transition shadow-md"
                            >
                                <svg class="h-4 w-4 fill-white" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                                </svg>
                                <span>{{ settings.pricing_monthly_cta_text || 'Langganan via Telegram' }}</span>
                            </a>
                        </div>
                    </div>

                    <!-- Card 3: Single Hospital License -->
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 flex flex-col justify-between space-y-6">
                        <div class="space-y-4">
                            <span class="rounded bg-slate-200 px-2 py-0.5 text-[11px] font-bold text-slate-700">
                                {{ settings.pricing_hospital_tag || 'Beli Putus Source Code' }}
                            </span>
                            <h3 class="text-xl font-bold text-slate-900">{{ settings.pricing_hospital_title || 'Single Hospital' }}</h3>
                            <p class="text-xs text-slate-600">{{ settings.pricing_hospital_desc || 'Full source code untuk diinstalasi di server lokal intranet 1 Rumah Sakit.' }}</p>

                            <div class="pt-2">
                                <div v-if="settings.pricing_hospital_promo_active && settings.pricing_hospital_strike_price" class="flex items-center gap-2 mb-1">
                                    <span class="text-sm font-bold text-slate-400 line-through">{{ formatRupiah(settings.pricing_hospital_strike_price) }}</span>
                                    <span class="rounded bg-emerald-50 px-1.5 py-0.2 text-[10px] font-bold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                        {{ settings.pricing_hospital_promo_badge || 'Hemat' }}
                                    </span>
                                </div>
                                <div class="flex items-baseline gap-1">
                                    <span class="text-3xl font-extrabold text-slate-900">{{ formatRupiah(settings.pricing_hospital_price ?? 4500000) }}</span>
                                    <span class="text-xs text-slate-500 font-semibold">{{ settings.pricing_hospital_period || ' (Sekali Bayar)' }}</span>
                                </div>
                            </div>

                            <ul class="space-y-2 text-xs text-slate-700 pt-3 border-t border-slate-200 font-medium">
                                <li
                                    v-for="(feat, fIdx) in (settings.pricing_hospital_features || ['✓ Full Source Code (Laravel + Vue)', '✓ Deploy Intranet (Kepatuhan PMK 24)', '✓ Tanpa Biaya Bulanan / Transaksi', '✓ Panduan Setup & Garansi 30 Hari'])"
                                    :key="fIdx"
                                    :class="fIdx === 0 ? 'font-semibold text-slate-800' : ''"
                                >
                                    {{ feat }}
                                </li>
                            </ul>
                        </div>

                        <div>
                            <a
                                :href="telegramHospitalUrl"
                                target="_blank"
                                class="flex items-center justify-center gap-2 w-full text-center rounded-lg border border-sky-300 bg-sky-50 hover:bg-sky-100 py-2.5 text-xs font-bold text-sky-900 transition shadow-2xs"
                            >
                                <svg class="h-4 w-4 fill-sky-600" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                                </svg>
                                <span>{{ settings.pricing_hospital_cta_text || 'Beli Source Code via Telegram' }}</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section: FAQ -->
        <section id="faq" class="py-16 bg-slate-50 border-b border-slate-200">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 space-y-8">
                <div class="text-center space-y-2">
                    <span class="font-mono text-xs font-bold text-teal-800 uppercase tracking-wider">Bantuan & Regulasi</span>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Tanya Jawab Seputar SIMRS Gateway</h2>
                </div>

                <div class="space-y-3">
                    <div
                        v-for="(faq, idx) in faqs"
                        :key="idx"
                        class="rounded-xl border border-slate-200 bg-white p-4 transition shadow-2xs"
                    >
                        <button
                            type="button"
                            class="flex w-full items-center justify-between text-left text-xs sm:text-sm font-bold text-slate-900"
                            @click="toggleFaq(idx)"
                        >
                            <span>{{ faq.q }}</span>
                            <span class="text-teal-800 text-lg font-bold ml-2">{{ openFaq === idx ? '−' : '+' }}</span>
                        </button>
                        <p v-if="openFaq === idx" class="mt-2 text-xs text-slate-600 leading-relaxed border-t border-slate-100 pt-2">
                            {{ faq.a }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-white py-10 text-xs text-slate-500">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-slate-800">SIMRS Gateway v3.0</span>
                    <span>•</span>
                    <span>Modul Integrasi BPJS Kesehatan Terlengkap</span>
                </div>
                <div class="flex flex-wrap items-center gap-4 sm:gap-5">
                    <a href="#arsitektur" class="hover:text-slate-800">Arsitektur</a>
                    <a href="#katalog" class="hover:text-slate-800">Katalog</a>
                    <a href="#lisensi" class="hover:text-slate-800">Harga Lisensi</a>
                    <a :href="telegramGeneralUrl" target="_blank" class="text-sky-600 font-bold hover:underline">Telegram @{{ telegramHandle }}</a>
                </div>
            </div>
        </footer>

        <!-- Floating Telegram Consultation Button (Direct to @FauziRahman95) -->
        <a
            :href="telegramGeneralUrl"
            target="_blank"
            title="Konsultasi Telegram ke @FauziRahman95"
            class="fixed bottom-6 right-6 z-50 flex items-center gap-2 rounded-full bg-sky-500 hover:bg-sky-600 px-4 py-3 text-xs font-bold text-white shadow-xl hover:scale-105 transition active:scale-95"
        >
            <svg class="h-4 w-4 fill-white" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
            </svg>
            <span>Telegram @{{ telegramHandle }}</span>
        </a>
    </div>
</template>
