<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

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
    { label: 'Open Source & Donasi', href: '#open-source', icon: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z' },
    { label: 'APM', href: '#apm', icon: 'M9 17v-2m3 2v-4m3 4v-6M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z', count: 'Segera' },
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

const saweriaUrl = 'https://saweria.co/fauzirahman05';
const githubRepoUrl = 'https://github.com/ahmadfauzirahman99/simrs-api';
const githubZipUrl = 'https://github.com/ahmadfauzirahman99/simrs-api/archive/refs/heads/feat/client-portal-dan-deploy.zip';
const postmanCollectionUrl = '/downloads/postman-collection';
const waGeneralUrl = computed(() => {
    const wa = settings.value.whatsapp_contact || props.whatsappNumber;
    const text = encodeURIComponent('Halo Mas Fauzi, saya ingin konsultasi mengenai implementasi SIMRS Gateway / jasa setup on-premise di RS kami.');
    return `https://wa.me/${wa}?text=${text}`;
});

const waConsultUrl = computed(() => {
    const wa = settings.value.whatsapp_contact || props.whatsappNumber;
    const text = encodeURIComponent('Halo Mas Fauzi, rumah sakit kami membutuhkan pendampingan teknis / jasa kustomisasi bridging SIMRS Gateway. Mohon info ketersediaan jadwal.');
    return `https://wa.me/${wa}?text=${text}`;
});

const telegramHandle = computed(() => {
    return (settings.value.telegram_contact || 'FauziRahman95').replace('@', '');
});

const telegramGeneralUrl = computed(() => {
    const handle = telegramHandle.value;
    const text = encodeURIComponent(`Halo mas @${handle}, saya pengguna SIMRS Gateway open-source, ingin konsultasi/tanya seputar implementasi.`);
    return `https://t.me/${handle}?text=${text}`;
});

const telegramConsultUrl = computed(() => {
    const handle = telegramHandle.value;
    const text = encodeURIComponent(`Halo mas @${handle}, rumah sakit kami membutuhkan jasa setup on-premise & asistensi bridging SIMRS Gateway. Mohon info ketersediaan dan prosedurnya.`);
    return `https://t.me/${handle}?text=${text}`;
});
const telegramApmUrl = computed(() => {
    const handle = telegramHandle.value;
    const text = encodeURIComponent(`Halo mas @${handle}, rumah sakit kami tertarik menjadi RS uji coba APM (Anjungan Pendaftaran Mandiri). Mohon info jadwal dan syaratnya.`);
    return `https://t.me/${handle}?text=${text}`;
});

const activeApmTab = ref('checkin'); // 'checkin' | 'daftar'

const apmCheckInSteps = [
    {
        title: 'Pasien Sentuh Menu "Check-In Kode Booking"',
        body: 'Pasien yang sudah mendaftar via Mobile JKN atau web faskes langsung mendatangi anjungan kiosk di lobi tanpa antre di loket admisi.',
    },
    {
        title: 'Scan QR Code atau Ketik Kode Booking (Huruf & Angka)',
        body: 'Dekatkan layar HP pasien ke barcode scanner kiosk atau masukkan kode booking (misal: JKN261001) melalui keypad layar sentuh.',
    },
    {
        title: 'Validasi & Otomatis Kirim Task ID 3 ke BPJS Antrol',
        body: 'Gateway memvalidasi jadwal hari ini dan mencatat Task ID 3 (waktu pasien tiba di RS & siap menuju poli) ke server BPJS Antrean Online.',
    },
    {
        title: 'Karcis Antrean Poli & SEP Tercetak Otomatis',
        body: 'Printer thermal kiosk mencetak karcis antrean poli dan SEP VClaim. Pasien langsung menuju ruang tunggu poliklinik.',
    },
];

const apmDaftarSteps = [
    {
        title: 'Pasien Sentuh Menu "Daftar Mandiri BPJS"',
        body: 'Untuk pasien rujukan baru atau pasien kontrol yang belum memiliki kode booking online sebelumnya (pasien datang langsung / go-show).',
    },
    {
        title: 'Ketik NIK KTP (16 Digit) atau No Kartu BPJS',
        body: 'Pasien memasukkan NIK atau nomor kartu BPJS via keypad layar sentuh, atau menempelkan KTP elektronik pada reader.',
    },
    {
        title: 'Cek Eligibilitas Rujukan & Surat Kontrol Aktif',
        body: 'Sistem mengecek status keaktifan BPJS, riwayat rujukan FKTP aktif, dan surat kontrol (Sukon) secara instan lewat bridging VClaim 2.0.',
    },
    {
        title: 'Pilih Poliklinik & Dokter Jaga Hari Ini',
        body: 'Pasien memilih dokter spesialis dan jam praktik yang tersedia kuotanya hari ini, lalu mesin menerbitkan nomor antrean poli.',
    },
];

const apmSteps = computed(() => activeApmTab.value === 'checkin' ? apmCheckInSteps : apmDaftarSteps);

// Satu-satunya animasi bagian APM: karcis keluar dari printer saat bagian terlihat.
const apmSection = ref(null);
const apmPrinted = ref(false);
let apmObserver = null;

onMounted(() => {
    if (!('IntersectionObserver' in window)) {
        apmPrinted.value = true;
        return;
    }
    apmObserver = new IntersectionObserver((entries) => {
        if (entries.some((entry) => entry.isIntersecting)) {
            apmPrinted.value = true;
            apmObserver?.disconnect();
        }
    }, { threshold: 0.35 });
    if (apmSection.value) apmObserver.observe(apmSection.value);
});

onBeforeUnmount(() => apmObserver?.disconnect());


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
        q: 'Apakah SIMRS Gateway ini benar-benar 100% Gratis & Open Source?',
        a: 'Ya, 100% Bebas & Terbuka. Anda bebas mengunduh source code lengkap (Laravel 12 + Vue 3), memodifikasi, dan memasangnya di server intranet lokal rumah sakit tanpa kewajiban membayar biaya lisensi bulanan, royalti per-pasien, atau biaya tersembunyi apa pun.',
    },
    {
        q: 'Bagaimana model bisnis proyek ini bertahan jika source code gratis?',
        a: 'Mengadopsi model open-source murni (seperti ekosistem Linux, Curl, SQLite): pengembangan inti didanai sukarela melalui donasi komunitas via Saweria (saweria.co/fauzirahman05). Pendapatan profesional diperoleh dari jasa nilai tambah opsional: asistensi instalasi on-site, kustomisasi bridging SIMRS lama, dan pendampingan UAT BPJS Cabang bagi rumah sakit yang membutuhkan.',
    },
    {
        q: 'Apakah sistem ini sesuai dengan regulasi PMK 24 / 2022 tentang Rekam Medis & UU PDP?',
        a: 'Sangat sesuai. Karena gateway open-source ini diinstal on-premise di server lokal rumah sakit Anda, seluruh data rekam medis pasien mengalir langsung dari database SIMRS lokal ke server BPJS Kesehatan / Kemenkes tanpa pernah melewati cloud pihak ketiga.',
    },
    {
        q: 'Bagaimana cara gateway menangani enkripsi AES-256 dan Signature BPJS?',
        a: 'SIMRS Gateway memiliki modul cipher bawaan yang secara otomatis menghitung HMAC-SHA256 timestamp signature, mengenkripsi payload request sebelum dikirim ke BPJS, dan mendekripsi balasan LZ-string secara instan. Aplikasi SIMRS Anda hanya perlu mengirim dan menerima data dalam format JSON standar.',
    },
    {
        q: 'Apakah bisa digunakan untuk persiapan UAT (User Acceptance Test) BPJS Cabang?',
        a: 'Ya. Salah satu fungsi utama gateway ini adalah sebagai simulator pengujian. Seluruh 209 endpoint telah dilengkapi dengan mock response dummy yang terstruktur rapi sesuai standar katalog BPJS Kesehatan, sehingga tim IT Anda siap melakukan demonstrasi UAT di hadapan tim IT Kantor Cabang BPJS.',
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
        <!-- Open Source & Donation Announcement Bar -->
        <div
            class="bg-gradient-to-r from-teal-950 via-teal-900 to-emerald-950 px-4 py-2 text-white text-xs font-semibold shadow-inner border-b border-teal-800/60"
        >
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-3">
                <div class="flex items-center gap-2 truncate">
                    <span class="inline-flex h-2 w-2 rounded-full bg-emerald-400 animate-pulse shrink-0"></span>
                    <span class="truncate text-[11px] sm:text-xs font-medium text-teal-100">
                        Proyek Open Source untuk Komunitas IT Rumah Sakit Indonesia — 100% Gratis & On-Premise
                    </span>
                </div>
                <a
                    :href="saweriaUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="shrink-0 rounded-full bg-amber-400 hover:bg-amber-300 text-slate-950 px-3 py-0.5 text-[10px] font-extrabold uppercase tracking-wider transition shadow-xs flex items-center gap-1"
                >
                    <span>☕ Dukung di Saweria</span>
                    <span>→</span>
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
                            <span class="hidden min-[400px]:inline rounded bg-teal-50 px-1.5 py-0.5 text-[10px] font-bold text-teal-800 border border-teal-200 whitespace-nowrap">v3.0</span>
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
                            class="hidden 2xl:inline rounded-full bg-teal-100 text-teal-800 px-1.5 py-0.2 text-[10px] font-bold"
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
                                    <div class="text-[10px] text-sky-700">Konsultasi Teknis & Jasa Setup RS</div>
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
                        <div class="inline-flex items-center gap-2 rounded-md border border-emerald-300 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-900">
                            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>100% Open Source (Gratis) • On-Premise Intranet Ready • PMK 24 / UU PDP</span>
                        </div>

                        <h1 class="text-3xl font-extrabold tracking-tight text-slate-950 sm:text-5xl sm:leading-[1.15]">
                            Infrastruktur Bridging BPJS Kesehatan Siap Pakai untuk Rumah Sakit.
                        </h1>

                        <p class="text-sm sm:text-base text-slate-600 leading-relaxed max-w-xl">
                            Hilangkan kerumitan kalkulasi signature timestamp, enkripsi AES-256, dan dekripsi LZ-string. Gateway lokal bebas pakai ini menyediakan <strong>209 endpoint terverifikasi</strong> dan simulator interaktif yang siap dipasang langsung di intranet RS Anda tanpa biaya lisensi.
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
                                :href="saweriaUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center justify-center gap-2 rounded-xl border border-amber-300 bg-amber-50 px-5 py-3 text-xs sm:text-sm font-bold text-amber-950 shadow-2xs hover:bg-amber-100 transition w-full sm:w-auto"
                            >
                                <span class="text-base">☕</span>
                                <span>Dukung via Saweria</span>
                            </a>
                        </div>

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

        <!-- Section: Open Source & Community Support -->
        <section id="open-source" class="py-16 bg-white border-b border-slate-200">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-12">
                <div class="text-center space-y-2">
                    <span class="font-mono text-xs font-bold text-emerald-700 uppercase tracking-wider">Filosofi & Dukungan</span>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                        100% Bebas & Terbuka untuk Seluruh Rumah Sakit Indonesia
                    </h2>
                    <p class="mx-auto max-w-2xl text-xs sm:text-sm text-slate-600">
                        Model open-source ala Linux & Curl: kode sumber bebas dipelajari, dimodifikasi, dan dijalankan di intranet RS Anda sendiri tanpa royalti. Didanai murni oleh donasi komunitas dan jasa konsultasi profesional.
                    </p>
                </div>

                <div class="grid gap-6 lg:grid-cols-3 max-w-6xl mx-auto items-stretch">
                    <!-- Card 1: Self-Hosted Open Source -->
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 flex flex-col justify-between space-y-6">
                        <div class="space-y-4">
                            <span class="rounded bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-800">
                                100% Free & Open Source
                            </span>
                            <h3 class="text-xl font-bold text-slate-900">Self-Host Bebas</h3>
                            <p class="text-xs text-slate-600">Unduh source code lengkap dan pasang langsung di server intranet lokal rumah sakit Anda.</p>
                            <div class="pt-2">
                                <span class="text-3xl font-extrabold text-slate-900">Rp 0</span>
                                <span class="text-xs text-slate-500 font-semibold"> (Gratis Selamanya)</span>
                            </div>

                            <ul class="space-y-2 text-xs text-slate-700 pt-3 border-t border-slate-200 font-medium">
                                <li>✓ Full Source Code (Laravel 12 + Vue 3)</li>
                                <li>✓ 209 Endpoint Resmi BPJS & SATUSEHAT</li>
                                <li>✓ On-Premise Intranet (Kepatuhan PMK 24)</li>
                                <li>✓ Tanpa Biaya Lisensi / Tanpa Batas Pasien</li>
                                <li>✓ Bebas Modifikasi Sesuai SIMRS Lokal</li>
                            </ul>
                        </div>

                        <div class="space-y-2 pt-2">
                            <a
                                :href="githubZipUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex items-center justify-center gap-2 w-full text-center rounded-lg bg-teal-800 hover:bg-teal-900 py-2.5 text-xs font-bold text-white transition shadow-sm"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                <span>Download Source Code (.ZIP)</span>
                            </a>

                            <div class="grid grid-cols-2 gap-2">
                                <a
                                    :href="postmanCollectionUrl"
                                    class="flex items-center justify-center gap-1.5 text-center rounded-lg border border-slate-300 bg-white py-2 text-[11px] font-bold text-slate-700 hover:bg-slate-100 transition shadow-2xs"
                                >
                                    <svg class="h-3.5 w-3.5 text-amber-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14.5v-9l6 4.5-6 4.5z"/>
                                    </svg>
                                    <span>Postman (209)</span>
                                </a>
                                <a
                                    :href="githubRepoUrl"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="flex items-center justify-center gap-1.5 text-center rounded-lg border border-slate-300 bg-white py-2 text-[11px] font-bold text-slate-700 hover:bg-slate-100 transition shadow-2xs"
                                >
                                    <svg class="h-3.5 w-3.5 text-slate-900" fill="currentColor" viewBox="0 0 24 24">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" />
                                    </svg>
                                    <span>GitHub Repo</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Saweria Community Sponsor (Featured / Highlight) -->
                    <div class="rounded-2xl border-2 border-amber-500 bg-gradient-to-b from-amber-50/50 to-white p-6 flex flex-col justify-between space-y-6 shadow-xl ring-4 ring-amber-100 relative overflow-hidden">
                        <!-- Heart Badge -->
                        <div class="absolute -top-1 -right-1 bg-gradient-to-r from-amber-500 to-rose-500 text-white font-extrabold text-[10px] px-4 py-1 rounded-bl-xl shadow-xs uppercase tracking-wider flex items-center gap-1">
                            <span>❤️ DUKUNG CREATOR</span>
                        </div>

                        <div class="space-y-4">
                            <span class="rounded bg-amber-100 px-2.5 py-0.5 text-[11px] font-bold text-amber-900">
                                Traktir Kopi & Donasi
                            </span>
                            <h3 class="text-xl font-bold text-slate-900">Dukung via Saweria</h3>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                Donasi Anda menjaga server pengujian publik tetap aktif, membiayai riset pembaruan endpoint BPJS terbaru, dan memastikan proyek ini tetap gratis untuk seluruh RS.
                            </p>

                            <div class="pt-2">
                                <div class="flex items-baseline gap-1">
                                    <span class="text-3xl font-extrabold text-amber-900">Sukarela</span>
                                    <span class="text-xs text-slate-500 font-semibold">(Mulai Rp10.000)</span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-0.5">Dukungan via QRIS, GoPay, OVO, DANA, ShopeePay & Bank Transfer.</p>
                            </div>

                            <ul class="space-y-2 text-xs text-slate-700 pt-3 border-t border-amber-200 font-medium">
                                <li class="font-bold text-amber-900">✓ Biaya operasional server demo publik</li>
                                <li>✓ Riset berkala update katalog BPJS & SatuSehat</li>
                                <li>✓ Pengembangan fitur APM & bridging baru</li>
                                <li>✓ Nama/RS Anda dicantumkan di list kontributor (opsional)</li>
                            </ul>
                        </div>

                        <div>
                            <a
                                :href="saweriaUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex items-center justify-center gap-2 w-full text-center rounded-lg bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 py-3 text-xs font-extrabold text-slate-950 transition shadow-md"
                            >
                                <span class="text-base">☕</span>
                                <span>Traktir Kopi di Saweria (fauzirahman05)</span>
                            </a>
                            <div class="text-center mt-2 text-[10px] text-slate-500 font-mono">
                                saweria.co/fauzirahman05
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Professional Setup & Assistance (Optional) -->
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 flex flex-col justify-between space-y-6">
                        <div class="space-y-4">
                            <span class="rounded bg-sky-100 px-2 py-0.5 text-[11px] font-bold text-sky-800">
                                Jasa Profesional (Opsional)
                            </span>
                            <h3 class="text-xl font-bold text-slate-900">Setup & Asistensi RS</h3>
                            <p class="text-xs text-slate-600">
                                Untuk rumah sakit yang membutuhkan bantuan instalasi on-premise, pendampingan UAT BPJS Cabang, atau kustomisasi bridging khusus SIMRS lama.
                            </p>

                            <div class="pt-2">
                                <span class="text-3xl font-extrabold text-slate-900">Sesuai Kebutuhan</span>
                                <span class="text-xs text-slate-500 font-semibold"> (Berdasarkan Ruang Lingkup)</span>
                            </div>

                            <ul class="space-y-2 text-xs text-slate-700 pt-3 border-t border-slate-200 font-medium">
                                <li>✓ Setup & Deployment On-Premise di Server RS</li>
                                <li>✓ Integrasi ke Database SIMRS Existing</li>
                                <li>✓ Pendampingan Teknis saat UAT BPJS Cabang</li>
                                <li>✓ Pelatihan Tim IT Internal Rumah Sakit</li>
                                <li>✓ SLA & Dukungan Teknis Langsung dari Pembuat</li>
                            </ul>
                        </div>

                        <div>
                            <a
                                :href="waConsultUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex items-center justify-center gap-2 w-full text-center rounded-lg border border-sky-300 bg-sky-50 hover:bg-sky-100 py-2.5 text-xs font-bold text-sky-900 transition shadow-2xs"
                            >
                                <svg class="h-4 w-4 fill-sky-600" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                                </svg>
                                <span>Konsultasi Kebutuhan RS</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section: APM (Anjungan Pendaftaran Mandiri) — produk berikutnya, belum dirilis -->
        <section id="apm" ref="apmSection" class="apm-section relative overflow-hidden border-b border-slate-800 bg-slate-950 py-16 lg:py-24 text-slate-300">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid items-center gap-12 lg:grid-cols-[1fr_0.9fr] lg:gap-16">
                    <div class="space-y-8">
                        <div class="space-y-5">
                            <span class="inline-flex items-center gap-2 rounded-full border border-teal-400/30 bg-teal-500/10 px-3 py-1 text-xs font-semibold text-teal-300">
                                <span class="h-1.5 w-1.5 rounded-full bg-teal-400"></span>
                                Fitur Tersedia & Terhubung BPJS Antrol
                            </span>
                            <h2 class="text-3xl font-extrabold tracking-[-0.025em] text-white sm:text-4xl sm:leading-[1.12] text-balance">
                                Pasien daftar sendiri di kios. SEP terbit tanpa antre di loket.
                            </h2>
                            <p class="max-w-xl text-sm sm:text-base leading-relaxed text-slate-400">
                                <strong class="font-semibold text-slate-200">APM (Anjungan Pasien Mandiri)</strong> adalah antarmuka layar sentuh di lobi RS yang terhubung ke SIMRS dan BPJS Kesehatan. Pasien mandiri memilih poli dan cetak SEP, petugas loket fokus melayani kasus khusus.
                            </p>

                            <!-- TAB SELECTOR CARA PAKAI APM -->
                            <div class="pt-2">
                                <p class="text-xs font-bold uppercase tracking-wider text-teal-400 mb-2.5">Pilih Cara / Alur Penggunaan:</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 p-1.5 rounded-xl bg-slate-900 border border-slate-800">
                                    <button
                                        type="button"
                                        @click="activeApmTab = 'checkin'"
                                        :class="[
                                            activeApmTab === 'checkin'
                                                ? 'bg-teal-700 text-white shadow-sm font-bold'
                                                : 'text-slate-400 hover:text-slate-200 font-medium',
                                            'px-3.5 py-2.5 rounded-lg text-xs transition cursor-pointer text-left'
                                        ]"
                                    >
                                        <div class="font-bold text-xs">Alur 1: Check-In Online</div>
                                        <div class="text-[11px] opacity-80">Sudah booking di Mobile JKN / Web</div>
                                    </button>
                                    <button
                                        type="button"
                                        @click="activeApmTab = 'daftar'"
                                        :class="[
                                            activeApmTab === 'daftar'
                                                ? 'bg-teal-700 text-white shadow-sm font-bold'
                                                : 'text-slate-400 hover:text-slate-200 font-medium',
                                            'px-3.5 py-2.5 rounded-lg text-xs transition cursor-pointer text-left'
                                        ]"
                                    >
                                        <div class="font-bold text-xs">Alur 2: Daftar Mandiri</div>
                                        <div class="text-[11px] opacity-80">Pasien datang langsung (Go-Show)</div>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- STEP BY STEP LIST -->
                        <ol class="space-y-4">
                            <li v-for="(step, idx) in apmSteps" :key="step.title" class="grid grid-cols-[2rem_1fr] gap-4">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full border border-teal-500/40 bg-teal-500/10 text-xs font-bold text-teal-300 tabular-nums">
                                    {{ idx + 1 }}
                                </span>
                                <div class="space-y-1 pt-0.5">
                                    <h3 class="text-sm font-bold text-white">{{ step.title }}</h3>
                                    <p class="max-w-md text-xs sm:text-sm leading-relaxed text-slate-400">{{ step.body }}</p>
                                </div>
                            </li>
                        </ol>

                        <!-- ACTION CTAs -->
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 pt-2">
                            <Link
                                href="/apm"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-teal-500 hover:bg-teal-400 px-5 py-3.5 text-xs sm:text-sm font-bold text-teal-950 shadow-[0_8px_24px_-8px_rgba(20,184,166,0.55)] transition active:scale-95"
                            >
                                <span>Buka Demo Kiosk APM (/apm)</span>
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </Link>
                            <a
                                :href="telegramApmUrl"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-700 bg-slate-900 hover:bg-slate-800 px-4 py-3.5 text-xs sm:text-sm font-semibold text-slate-300 transition"
                            >
                                <span>Konsultasi Implementasi RS</span>
                            </a>
                        </div>
                    </div>

                    <!-- Tiruan layar kios (interaktif mengikuti tab aktif) -->
                    <figure class="relative mx-auto w-full max-w-md">
                        <div class="rounded-[1.75rem] border border-slate-700 bg-slate-900 p-3 shadow-[0_30px_60px_-20px_rgba(0,0,0,0.7)]">
                            <div class="overflow-hidden rounded-[1.1rem] bg-slate-50 text-slate-900">
                                <div class="flex items-center justify-between bg-teal-800 px-5 py-3 text-white">
                                    <span class="text-xs font-bold">{{ activeApmTab === 'checkin' ? 'Check-In Mobile JKN' : 'Pendaftaran Mandiri BPJS' }}</span>
                                    <span class="text-[11px] tabular-nums text-teal-100">08:15</span>
                                </div>
                                <div class="space-y-4 p-5">
                                    <div class="space-y-1">
                                        <p class="text-[11px] font-semibold text-slate-400">
                                            {{ activeApmTab === 'checkin' ? 'Verifikasi Kode Booking' : 'Langkah 3 dari 3' }}
                                        </p>
                                        <p class="text-lg font-bold tracking-[-0.02em] text-slate-900">
                                            {{ activeApmTab === 'checkin' ? 'Check-In Berhasil Terkonfirmasi' : 'Pendaftaran Berhasil' }}
                                        </p>
                                    </div>
                                    <dl class="divide-y divide-slate-200 rounded-xl border border-slate-200 bg-white text-xs">
                                        <div class="flex justify-between px-4 py-2.5">
                                            <dt class="text-slate-400">{{ activeApmTab === 'checkin' ? 'Kode Booking' : 'Pasien' }}</dt>
                                            <dd class="font-semibold">{{ activeApmTab === 'checkin' ? 'JKN261001' : 'Ny. Rahmawati' }}</dd>
                                        </div>
                                        <div class="flex justify-between px-4 py-2.5">
                                            <dt class="text-slate-400">Poliklinik</dt>
                                            <dd class="font-semibold">Penyakit Dalam</dd>
                                        </div>
                                        <div class="flex justify-between px-4 py-2.5">
                                            <dt class="text-slate-400">Dokter</dt>
                                            <dd class="font-semibold">dr. Budi Santoso, Sp.PD</dd>
                                        </div>
                                        <div class="flex justify-between px-4 py-2.5">
                                            <dt class="text-slate-400">Status BPJS</dt>
                                            <dd class="font-semibold text-teal-700">
                                                {{ activeApmTab === 'checkin' ? 'Task 3 Tercatat (Tiba di RS)' : 'SEP & Antrean Terbit' }}
                                            </dd>
                                        </div>
                                    </dl>
                                    <div class="flex items-center gap-2 rounded-xl bg-teal-50 px-4 py-3 text-xs font-semibold text-teal-900">
                                        <svg class="h-4 w-4 shrink-0 text-teal-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                        </svg>
                                        Silakan ambil karcis antrean di bawah layar
                                    </div>
                                </div>
                            </div>
                            <!-- Celah printer -->
                            <div class="mx-auto mt-3 h-1.5 w-40 rounded-full bg-slate-950 shadow-[inset_0_1px_2px_rgba(0,0,0,0.8)]"></div>
                        </div>

                        <!-- Karcis antrean thermal -->
                        <div class="apm-ticket-clip mx-auto w-36">
                            <div class="apm-ticket mx-auto w-36 rounded-b-lg bg-white px-3 pb-3 pt-2 text-center text-slate-900 shadow-[0_12px_24px_-12px_rgba(0,0,0,0.6)]" :class="{ 'is-printed': apmPrinted }">
                                <p class="text-[9px] font-semibold text-slate-400">Nomor antrean</p>
                                <p class="text-2xl font-extrabold tracking-[-0.03em] tabular-nums text-teal-800">
                                    {{ activeApmTab === 'checkin' ? 'INT-001' : 'INT-002' }}
                                </p>
                                <p class="text-[9px] text-slate-400">Poli Penyakit Dalam</p>
                            </div>
                        </div>

                        <div class="mt-4 text-center">
                            <Link href="/apm" class="inline-flex items-center gap-1.5 text-xs font-bold text-teal-400 hover:text-teal-300">
                                <span>Buka Layar Kiosk Layar Penuh</span>
                                <span>&rarr;</span>
                            </Link>
                        </div>
                    </figure>
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
                    <a href="#open-source" class="hover:text-slate-800">Open Source & Donasi</a>
                    <a :href="saweriaUrl" target="_blank" rel="noopener noreferrer" class="text-amber-600 font-bold hover:underline">Saweria</a>
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
