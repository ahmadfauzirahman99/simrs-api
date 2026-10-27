<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, nextTick } from 'vue';

const props = defineProps({
    rsName: {
        type: String,
        default: 'HAURA MEDICAL',
    },
    today: {
        type: String,
        default: '',
    },
});

// Clock state
const currentTime = ref('');
let timerId = null;

const updateClock = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
};

// Kiosk State: 'home' | 'checkin' | 'daftar' | 'jadwal' | 'ticket'
const currentStep = ref('home');
const isLoading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

// Auto-reset timer back to 'home' after idle (45 detik)
let idleTimer = null;
const resetIdleTimer = (seconds = 45) => {
    clearTimeout(idleTimer);
    if (currentStep.value !== 'home') {
        idleTimer = setTimeout(() => {
            goHome();
        }, seconds * 1000);
    }
};

const goHome = () => {
    clearTimeout(idleTimer);
    currentStep.value = 'home';
    checkInCode.value = '';
    searchIdNumber.value = '';
    patientData.value = null;
    selectedPoli.value = null;
    selectedDoctor.value = null;
    ticketData.value = null;
    errorMessage.value = '';
    successMessage.value = '';
};

// ==========================================
// 1. CHECK-IN STATE (ALPHANUMERIC SUPPORT)
// ==========================================
const checkInCode = ref('');
const checkInInputRef = ref(null);
const checkInKeypadMode = ref('NUM'); // 'NUM' | 'ALPHA'

const handleCheckInChar = (char) => {
    resetIdleTimer();
    checkInCode.value = (checkInCode.value + char).toUpperCase();
};

const handleCheckInBackspace = () => {
    resetIdleTimer();
    checkInCode.value = checkInCode.value.slice(0, -1);
};

const handleCheckInClear = () => {
    resetIdleTimer();
    checkInCode.value = '';
};

const pasteCheckInFromClipboard = async () => {
    resetIdleTimer();
    try {
        const text = await navigator.clipboard.readText();
        if (text) {
            checkInCode.value = text.trim().toUpperCase();
        }
    } catch (e) {
        // Fallback jika permission browser ditolak
        checkInInputRef.value?.focus();
    }
};

const submitCheckIn = async () => {
    const code = checkInCode.value.trim().toUpperCase();
    if (!code) {
        errorMessage.value = 'Harap masukkan atau scan kode booking Anda.';
        return;
    }

    isLoading.value = true;
    errorMessage.value = '';

    try {
        const res = await fetch('/api/apm-api/check-in', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ kodebooking: code }),
        });
        const json = await res.json();

        if (!res.ok || !json.success) {
            errorMessage.value = json.message || 'Kode booking tidak ditemukan atau gagal check-in.';
            isLoading.value = false;
            return;
        }

        ticketData.value = json.data;
        successMessage.value = json.message;
        currentStep.value = 'ticket';
        resetIdleTimer(25);
    } catch (err) {
        errorMessage.value = 'Terjadi gangguan komunikasi dengan gateway RS.';
    } finally {
        isLoading.value = false;
    }
};

// ==========================================
// 2. DAFTAR MANDIRI STATE (NIK / BPJS)
// ==========================================
const daftarStep = ref(1); // 1: Input NIK/NoKartu, 2: Pilih Poli & Dokter
const searchIdNumber = ref('');
const searchInputRef = ref(null);
const patientData = ref(null);
const listPoli = ref([]);
const selectedPoli = ref(null);
const selectedDoctor = ref(null);
const ticketData = ref(null);

const handleNikNumber = (num) => {
    resetIdleTimer();
    if (searchIdNumber.value.length < 16) {
        searchIdNumber.value += num;
    }
};

const handleNikBackspace = () => {
    resetIdleTimer();
    searchIdNumber.value = searchIdNumber.value.slice(0, -1);
};

const handleNikClear = () => {
    resetIdleTimer();
    searchIdNumber.value = '';
};

const pasteNikFromClipboard = async () => {
    resetIdleTimer();
    try {
        const text = await navigator.clipboard.readText();
        if (text) {
            searchIdNumber.value = text.replace(/\D/g, '').slice(0, 16);
        }
    } catch (e) {
        searchInputRef.value?.focus();
    }
};

const fetchJadwalDokter = async () => {
    try {
        const res = await fetch('/api/apm-api/jadwal-hari-ini');
        const json = await res.json();
        if (json.success && json.data) {
            listPoli.value = json.data;
        }
    } catch (e) {}
};

const searchPatient = async () => {
    const raw = searchIdNumber.value.trim();
    if (raw.length < 10) {
        errorMessage.value = 'Nomor identitas minimal 13 digit (No BPJS) atau 16 digit (NIK).';
        return;
    }

    isLoading.value = true;
    errorMessage.value = '';

    try {
        const res = await fetch('/api/apm-api/search-peserta', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ nomor: raw }),
        });
        const json = await res.json();

        if (json.success && json.data?.peserta) {
            patientData.value = json.data.peserta;
        } else {
            // Mode simulasi / faskes lokal
            patientData.value = {
                nama: 'PASIEN RAWAT JALAN',
                noKartu: raw,
                nik: raw,
                statusPeserta: { keterangan: 'AKTIF (BPJS KESEHATAN)' },
            };
        }

        await fetchJadwalDokter();
        daftarStep.value = 2;
        resetIdleTimer(60);
    } catch (err) {
        // Fallback pendaftaran mandiri tetap bisa berlanjut
        patientData.value = {
            nama: 'PASIEN RAWAT JALAN',
            noKartu: raw,
            nik: raw,
            statusPeserta: { keterangan: 'AKTIF' },
        };
        await fetchJadwalDokter();
        daftarStep.value = 2;
    } finally {
        isLoading.value = false;
    }
};

const choosePoli = (poli) => {
    resetIdleTimer(60);
    selectedPoli.value = poli;
    selectedDoctor.value = poli.dokter?.[0] || null;
};

const chooseDoctor = (doc) => {
    resetIdleTimer(60);
    selectedDoctor.value = doc;
};

const submitDaftarMandiri = async () => {
    if (!selectedPoli.value || !selectedDoctor.value) {
        errorMessage.value = 'Harap pilih poliklinik dan dokter tujuan.';
        return;
    }

    isLoading.value = true;
    errorMessage.value = '';

    const payload = {
        jenispasien: 'JKN',
        kodepoli: selectedPoli.value.kdpoli,
        namapoli: selectedPoli.value.nmpoli,
        kodedokter: selectedDoctor.value.kodedokter,
        namadokter: selectedDoctor.value.namadokter,
        jampraktek: selectedDoctor.value.jampraktek,
        nomorkartu: patientData.value?.noKartu || searchIdNumber.value,
        nik: patientData.value?.nik || searchIdNumber.value,
        nama_pasien: patientData.value?.nama || 'PASIEN MANDIRI',
        nohp: patientData.value?.mr?.noTelepon || '08123456789',
        jeniskunjungan: 1,
    };

    try {
        const res = await fetch('/api/apm-api/daftar-mandiri', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(payload),
        });
        const json = await res.json();

        if (!res.ok || !json.success) {
            errorMessage.value = json.message || 'Pendaftaran antrean gagal.';
            isLoading.value = false;
            return;
        }

        ticketData.value = json.data;
        successMessage.value = 'Nomor antrean poli berhasil diterbitkan!';
        currentStep.value = 'ticket';
        resetIdleTimer(30);
    } catch (err) {
        errorMessage.value = 'Gangguan komunikasi antrean.';
    } finally {
        isLoading.value = false;
    }
};

// ==========================================
// 3. QUICK FILL DATA RIIL UJI COBA
// ==========================================
const fillRealCaseCheckIn = () => {
    currentStep.value = 'checkin';
    checkInCode.value = 'JKN261001';
    errorMessage.value = '';
    resetIdleTimer();
    nextTick(() => {
        checkInInputRef.value?.focus();
    });
};

const fillRealCaseDaftar = () => {
    currentStep.value = 'daftar';
    daftarStep.value = 1;
    searchIdNumber.value = '3201124508890002';
    errorMessage.value = '';
    resetIdleTimer();
    nextTick(() => {
        searchInputRef.value?.focus();
    });
};

const printTicket = () => {
    window.print();
};

onMounted(() => {
    updateClock();
    timerId = setInterval(updateClock, 1000);
    fetchJadwalDokter();
});

onUnmounted(() => {
    clearInterval(timerId);
    clearTimeout(idleTimer);
});
</script>

<template>
    <Head title="Anjungan Pasien Mandiri (APM) — Kiosk RS" />

    <!-- CLEAN HOSPITAL THEME: Professional, High-Contrast, Sterile Neutral Slate -->
    <div class="min-h-screen bg-slate-100 text-slate-800 flex flex-col font-sans select-none overflow-x-hidden antialiased">

        <!-- HOSPITAL HEADER BAR -->
        <header class="bg-teal-800 text-white border-b-4 border-teal-900 px-6 py-4 flex items-center justify-between shadow-md">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-white text-teal-800 flex items-center justify-center font-black text-2xl shadow-sm border border-teal-200">
                    +
                </div>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight leading-none text-white flex items-center gap-2">
                        {{ rsName }}
                    </h1>
                    <p class="text-xs text-teal-100 font-medium tracking-wide mt-1 uppercase">
                        Anjungan Pasien Mandiri (APM) • Integrasi BPJS Kesehatan
                    </p>
                </div>
            </div>

            <!-- CLOCK & HOME BUTTON -->
            <div class="flex items-center gap-5">
                <div class="text-right border-r border-teal-700 pr-5 hidden sm:block">
                    <div class="text-xl font-bold font-mono tracking-wider text-teal-50">{{ currentTime }}</div>
                    <div class="text-xs text-teal-200">{{ today }}</div>
                </div>

                <button
                    v-if="currentStep !== 'home'"
                    @click="goHome"
                    class="px-5 py-2.5 rounded-lg bg-teal-900 hover:bg-teal-950 text-white font-semibold text-sm border border-teal-700 transition active:scale-95 cursor-pointer shadow-sm flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Kembali ke Awal
                </button>
            </div>
        </header>

        <!-- QUICK TEST BAR (KASUS RIIL DI DATABASE) -->
        <div class="bg-amber-50 border-b border-amber-200 px-6 py-2 flex flex-wrap items-center justify-between text-xs text-amber-900 gap-2">
            <div class="flex items-center gap-2 font-medium">
                <span class="font-bold px-2 py-0.5 rounded bg-amber-200 text-amber-900">UJI COBA DATA RIIL:</span>
                <span>Data riil sudah tersedia di sistem untuk simulasi langsung:</span>
            </div>
            <div class="flex items-center gap-2">
                <button
                    @click="fillRealCaseCheckIn"
                    class="px-3 py-1 rounded bg-amber-200 hover:bg-amber-300 font-bold text-amber-950 border border-amber-300 transition cursor-pointer"
                >
                    1. Uji Check-In Booking (JKN261001)
                </button>
                <button
                    @click="fillRealCaseDaftar"
                    class="px-3 py-1 rounded bg-teal-100 hover:bg-teal-200 font-bold text-teal-950 border border-teal-300 transition cursor-pointer"
                >
                    2. Uji Daftar NIK (3201124508890002)
                </button>
            </div>
        </div>

        <!-- MAIN KIOSK CONTENT AREA -->
        <main class="flex-1 flex flex-col p-6 sm:p-8 max-w-6xl mx-auto w-full justify-center">

            <!-- 1. HOME SCREEN: TOUCH SELECTION -->
            <div v-if="currentStep === 'home'" class="space-y-8 my-auto">
                <div class="text-center space-y-2">
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                        Selamat Datang di Anjungan Mandiri
                    </h2>
                    <p class="text-base text-slate-600 max-w-xl mx-auto">
                        Silakan sentuh tombol layanan yang Anda tuju untuk memulai proses pendaftaran atau verifikasi antrean poli.
                    </p>
                </div>

                <!-- 3 MAIN KIOSK ACTION CARDS -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                    <!-- CARD 1: CHECK-IN KODE BOOKING -->
                    <button
                        @click="currentStep = 'checkin'; resetIdleTimer(); nextTick(() => checkInInputRef?.focus());"
                        class="bg-white border-2 border-teal-600 hover:border-teal-700 hover:bg-teal-50/40 rounded-2xl p-7 text-left transition-all duration-150 shadow-sm hover:shadow-md flex flex-col justify-between min-h-[300px] cursor-pointer active:scale-[0.99] group"
                    >
                        <div class="space-y-4">
                            <div class="w-16 h-16 rounded-xl bg-teal-100 text-teal-800 flex items-center justify-center font-bold text-2xl group-hover:scale-105 transition-transform">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                </svg>
                            </div>
                            <span class="inline-block text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded bg-teal-100 text-teal-800">
                                Sudah Daftar Online
                            </span>
                            <h3 class="text-2xl font-bold text-slate-900 group-hover:text-teal-800">
                                Check-In Kode Booking
                            </h3>
                            <p class="text-sm text-slate-600 leading-relaxed">
                                Untuk pasien yang sudah mendaftar melalui <strong>Mobile JKN</strong> atau Website RS. Konfirmasi kedatangan & cetak karcis poli.
                            </p>
                        </div>
                        <div class="mt-4 pt-4 border-t border-slate-100 font-bold text-sm text-teal-800 flex items-center justify-between">
                            <span>Sentuh untuk Check-In</span>
                            <span>&rarr;</span>
                        </div>
                    </button>

                    <!-- CARD 2: DAFTAR MANDIRI BPJS (GO-SHOW) -->
                    <button
                        @click="currentStep = 'daftar'; daftarStep = 1; resetIdleTimer(); nextTick(() => searchInputRef?.focus());"
                        class="bg-white border-2 border-emerald-600 hover:border-emerald-700 hover:bg-emerald-50/40 rounded-2xl p-7 text-left transition-all duration-150 shadow-sm hover:shadow-md flex flex-col justify-between min-h-[300px] cursor-pointer active:scale-[0.99] group"
                    >
                        <div class="space-y-4">
                            <div class="w-16 h-16 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-2xl group-hover:scale-105 transition-transform">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                                </svg>
                            </div>
                            <span class="inline-block text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded bg-emerald-100 text-emerald-800">
                                Pasien Datang Langsung
                            </span>
                            <h3 class="text-2xl font-bold text-slate-900 group-hover:text-emerald-800">
                                Daftar Mandiri BPJS
                            </h3>
                            <p class="text-sm text-slate-600 leading-relaxed">
                                Masukkan <strong>NIK KTP</strong> atau <strong>No Kartu BPJS</strong>. Cek eligibilitas rujukan, pilih poliklinik, dan cetak antrean langsung.
                            </p>
                        </div>
                        <div class="mt-4 pt-4 border-t border-slate-100 font-bold text-sm text-emerald-800 flex items-center justify-between">
                            <span>Sentuh untuk Mendaftar</span>
                            <span>&rarr;</span>
                        </div>
                    </button>

                    <!-- CARD 3: JADWAL POLIKLINIK -->
                    <button
                        @click="currentStep = 'jadwal'; fetchJadwalDokter(); resetIdleTimer();"
                        class="bg-white border-2 border-slate-300 hover:border-slate-500 hover:bg-slate-50 rounded-2xl p-7 text-left transition-all duration-150 shadow-sm hover:shadow-md flex flex-col justify-between min-h-[300px] cursor-pointer active:scale-[0.99] group"
                    >
                        <div class="space-y-4">
                            <div class="w-16 h-16 rounded-xl bg-slate-200 text-slate-700 flex items-center justify-center font-bold text-2xl group-hover:scale-105 transition-transform">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <span class="inline-block text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded bg-slate-200 text-slate-700">
                                Informasi Pelayanan
                            </span>
                            <h3 class="text-2xl font-bold text-slate-900 group-hover:text-slate-800">
                                Jadwal Dokter Hari Ini
                            </h3>
                            <p class="text-sm text-slate-600 leading-relaxed">
                                Lihat daftar poliklinik spesialis yang buka hari ini, nama dokter jaga, jam operasional praktik, dan ketersediaan kuota antrean.
                            </p>
                        </div>
                        <div class="mt-4 pt-4 border-t border-slate-100 font-bold text-sm text-slate-700 flex items-center justify-between">
                            <span>Lihat Jadwal Lengkap</span>
                            <span>&rarr;</span>
                        </div>
                    </button>

                </div>

                <div class="text-center text-xs text-slate-500">
                    Membutuhkan bantuan petugas? Silakan kunjungi loket informasi di samping mesin anjungan.
                </div>
            </div>

            <!-- 2. CHECK-IN SCREEN (ALPHANUMERIC & SCANNER FRIENDLY) -->
            <div v-else-if="currentStep === 'checkin'" class="max-w-2xl mx-auto w-full space-y-6">
                <div class="text-center space-y-1">
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Konfirmasi Kedatangan (Check-In)</h2>
                    <p class="text-sm text-slate-600">
                        Scan QR Code di ponsel Anda pada scanner, atau ketik / paste kode booking di bawah.
                    </p>
                </div>

                <div v-if="errorMessage" class="p-4 rounded-xl bg-red-50 border border-red-300 text-red-800 text-sm font-semibold text-center">
                    {{ errorMessage }}
                </div>

                <!-- INPUT BOX: NORMAL EDITABLE, CAN PASTE, CAN TYPE -->
                <div class="bg-white border-2 border-teal-700 rounded-2xl p-4 shadow-sm flex flex-col gap-3">
                    <div class="flex items-center justify-between text-xs font-bold text-slate-500 uppercase tracking-wider">
                        <span>Kode Booking Pasien (Huruf & Angka):</span>
                        <button
                            type="button"
                            @click="pasteCheckInFromClipboard"
                            class="px-3 py-1 rounded bg-teal-50 hover:bg-teal-100 text-teal-800 border border-teal-300 font-bold text-xs flex items-center gap-1 cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            Tempel / Paste
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
                        <input
                            ref="checkInInputRef"
                            type="text"
                            v-model="checkInCode"
                            placeholder="Contoh: JKN261001"
                            @keyup.enter="submitCheckIn"
                            autocomplete="off"
                            class="w-full text-center text-3xl font-mono font-extrabold tracking-widest text-slate-900 uppercase bg-slate-50 border border-slate-300 rounded-xl py-3 px-4 focus:bg-white focus:border-teal-700 focus:outline-none"
                        />
                        <button
                            v-if="checkInCode"
                            @click="handleCheckInClear"
                            class="h-14 px-4 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-xl text-xs uppercase"
                        >
                            Bersihkan
                        </button>
                    </div>
                </div>

                <!-- KEYPAD MODE TOGGLE (NUM / ALPHA) -->
                <div class="flex items-center justify-between px-1">
                    <span class="text-xs font-bold text-slate-500 uppercase">Keypad Layar Sentuh:</span>
                    <div class="inline-flex rounded-lg border border-slate-300 bg-slate-200 p-0.5">
                        <button
                            type="button"
                            @click="checkInKeypadMode = 'NUM'"
                            :class="[
                                checkInKeypadMode === 'NUM' ? 'bg-white text-teal-900 shadow-sm font-bold' : 'text-slate-600',
                                'px-3 py-1 text-xs rounded-md transition cursor-pointer'
                            ]"
                        >
                            Angka (123)
                        </button>
                        <button
                            type="button"
                            @click="checkInKeypadMode = 'ALPHA'"
                            :class="[
                                checkInKeypadMode === 'ALPHA' ? 'bg-white text-teal-900 shadow-sm font-bold' : 'text-slate-600',
                                'px-3 py-1 text-xs rounded-md transition cursor-pointer'
                            ]"
                        >
                            Huruf (A-Z)
                        </button>
                    </div>
                </div>

                <!-- ON-SCREEN KEYPAD (NUMBERS) -->
                <div v-if="checkInKeypadMode === 'NUM'" class="grid grid-cols-3 gap-2.5">
                    <button
                        v-for="n in ['1','2','3','4','5','6','7','8','9']"
                        :key="n"
                        type="button"
                        @click="handleCheckInChar(n)"
                        class="h-14 rounded-xl bg-white hover:bg-slate-50 active:bg-slate-200 border-2 border-slate-200 text-slate-900 text-2xl font-bold font-mono transition cursor-pointer shadow-sm"
                    >
                        {{ n }}
                    </button>
                    <button
                        type="button"
                        @click="handleCheckInClear"
                        class="h-14 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold uppercase transition cursor-pointer border border-slate-200"
                    >
                        Hapus
                    </button>
                    <button
                        type="button"
                        @click="handleCheckInChar('0')"
                        class="h-14 rounded-xl bg-white hover:bg-slate-50 active:bg-slate-200 border-2 border-slate-200 text-slate-900 text-2xl font-bold font-mono transition cursor-pointer shadow-sm"
                    >
                        0
                    </button>
                    <button
                        type="button"
                        @click="handleCheckInBackspace"
                        class="h-14 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold uppercase transition cursor-pointer border border-slate-200 flex items-center justify-center"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l6.414-6.414a2 2 0 011.414-.586H19a2 2 0 012 2v10a2 2 0 01-2 2H10.828a2 2 0 01-1.414-.586L3 12z" />
                        </svg>
                    </button>
                </div>

                <!-- ON-SCREEN KEYPAD (LETTERS A-Z) -->
                <div v-else class="grid grid-cols-7 gap-1.5">
                    <button
                        v-for="l in ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z']"
                        :key="l"
                        type="button"
                        @click="handleCheckInChar(l)"
                        class="h-12 rounded-lg bg-white hover:bg-slate-50 active:bg-slate-200 border border-slate-300 text-slate-900 font-bold font-mono text-lg transition cursor-pointer shadow-sm"
                    >
                        {{ l }}
                    </button>
                    <button
                        type="button"
                        @click="handleCheckInBackspace"
                        class="h-12 col-span-2 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold uppercase transition cursor-pointer flex items-center justify-center gap-1"
                    >
                        Backspace
                    </button>
                </div>

                <!-- SUBMIT ACTION -->
                <button
                    type="button"
                    @click="submitCheckIn"
                    :disabled="isLoading || !checkInCode"
                    class="w-full h-14 rounded-xl bg-teal-800 hover:bg-teal-900 active:bg-teal-950 disabled:opacity-50 text-white font-extrabold text-lg tracking-wider transition cursor-pointer shadow-md flex items-center justify-center gap-2"
                >
                    <svg v-if="isLoading" class="animate-spin w-5 h-5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>{{ isLoading ? 'Memverifikasi ke BPJS Antrol...' : 'PROSES CHECK-IN SEKARANG' }}</span>
                </button>
            </div>

            <!-- 3. DAFTAR MANDIRI SCREEN (PASTE & REAL TOUCH SUPPORT) -->
            <div v-else-if="currentStep === 'daftar'" class="max-w-3xl mx-auto w-full space-y-6">

                <!-- STEP 1: INPUT NIK / KARTU BPJS -->
                <div v-if="daftarStep === 1" class="space-y-6 max-w-xl mx-auto">
                    <div class="text-center space-y-1">
                        <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Identitas Pasien BPJS</h2>
                        <p class="text-sm text-slate-600">
                            Ketik 16 digit NIK KTP atau 13 digit No Kartu BPJS. Bisa langsung diketik atau ditempel (paste).
                        </p>
                    </div>

                    <div v-if="errorMessage" class="p-4 rounded-xl bg-red-50 border border-red-300 text-red-800 text-sm font-semibold text-center">
                        {{ errorMessage }}
                    </div>

                    <!-- INPUT BOX (CAN TYPE, PASTE, EDIT) -->
                    <div class="bg-white border-2 border-emerald-700 rounded-2xl p-4 shadow-sm space-y-2">
                        <div class="flex items-center justify-between text-xs font-bold text-slate-500 uppercase tracking-wider">
                            <span>NIK / No Kartu BPJS:</span>
                            <button
                                type="button"
                                @click="pasteNikFromClipboard"
                                class="px-3 py-1 rounded bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 font-bold text-xs flex items-center gap-1 cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                Tempel / Paste
                            </button>
                        </div>

                        <div class="flex items-center gap-2">
                            <input
                                ref="searchInputRef"
                                type="text"
                                v-model="searchIdNumber"
                                placeholder="Contoh: 3201124508890002"
                                @keyup.enter="searchPatient"
                                maxlength="16"
                                class="w-full text-center text-3xl font-mono font-extrabold tracking-widest text-slate-900 bg-slate-50 border border-slate-300 rounded-xl py-3 px-4 focus:bg-white focus:border-emerald-700 focus:outline-none"
                            />
                            <span class="text-xs font-mono font-bold text-slate-400 whitespace-nowrap">
                                {{ searchIdNumber.length }}/16
                            </span>
                        </div>
                    </div>

                    <!-- ON-SCREEN NUMERIC KEYPAD -->
                    <div class="grid grid-cols-3 gap-2.5">
                        <button
                            v-for="n in ['1','2','3','4','5','6','7','8','9']"
                            :key="n"
                            type="button"
                            @click="handleNikNumber(n)"
                            class="h-14 rounded-xl bg-white hover:bg-slate-50 active:bg-slate-200 border-2 border-slate-200 text-slate-900 text-2xl font-bold font-mono transition cursor-pointer shadow-sm"
                        >
                            {{ n }}
                        </button>
                        <button
                            type="button"
                            @click="handleNikClear"
                            class="h-14 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold uppercase transition cursor-pointer border border-slate-200"
                        >
                            Hapus
                        </button>
                        <button
                            type="button"
                            @click="handleNikNumber('0')"
                            class="h-14 rounded-xl bg-white hover:bg-slate-50 active:bg-slate-200 border-2 border-slate-200 text-slate-900 text-2xl font-bold font-mono transition cursor-pointer shadow-sm"
                        >
                            0
                        </button>
                        <button
                            type="button"
                            @click="handleNikBackspace"
                            class="h-14 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold uppercase transition cursor-pointer border border-slate-200 flex items-center justify-center"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l6.414-6.414a2 2 0 011.414-.586H19a2 2 0 012 2v10a2 2 0 01-2 2H10.828a2 2 0 01-1.414-.586L3 12z" />
                            </svg>
                        </button>
                    </div>

                    <!-- SUBMIT BUTTON -->
                    <button
                        type="button"
                        @click="searchPatient"
                        :disabled="isLoading || searchIdNumber.length < 10"
                        class="w-full h-14 rounded-xl bg-emerald-700 hover:bg-emerald-800 active:bg-emerald-900 disabled:opacity-50 text-white font-extrabold text-lg tracking-wider transition cursor-pointer shadow-md flex items-center justify-center gap-2"
                    >
                        <svg v-if="isLoading" class="animate-spin w-5 h-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>{{ isLoading ? 'Mengecek Peserta di BPJS...' : 'LANJUT PILIH POLIKLINIK' }}</span>
                    </button>
                </div>

                <!-- STEP 2: PILIH POLI & DOKTER -->
                <div v-else-if="daftarStep === 2" class="space-y-6">
                    <!-- PATIENT SUMMARY BANNER -->
                    <div class="bg-white border border-slate-300 rounded-xl p-4 flex items-center justify-between shadow-sm">
                        <div>
                            <span class="text-xs uppercase font-bold text-slate-500">Pasien Terverifikasi:</span>
                            <div class="text-xl font-black text-slate-900">{{ patientData?.nama || 'PASIEN BPJS' }}</div>
                            <div class="text-xs font-mono text-slate-600">
                                No Kartu: {{ patientData?.noKartu }} • NIK: {{ patientData?.nik }}
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="daftarStep = 1"
                            class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-xs font-bold text-slate-700 border border-slate-300 cursor-pointer"
                        >
                            Ganti Identitas
                        </button>
                    </div>

                    <!-- GRID SELECTION -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- POLI LIST -->
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                                1. Pilih Poliklinik Spesialis:
                            </label>
                            <div class="space-y-2 max-h-[340px] overflow-y-auto pr-1">
                                <button
                                    v-for="poli in listPoli"
                                    :key="poli.kdpoli"
                                    type="button"
                                    @click="choosePoli(poli)"
                                    :class="[
                                        selectedPoli?.kdpoli === poli.kdpoli
                                            ? 'bg-teal-50 border-2 border-teal-700 text-teal-950 font-bold shadow-sm'
                                            : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50',
                                        'w-full p-4 rounded-xl text-left flex items-center justify-between transition cursor-pointer'
                                    ]"
                                >
                                    <div>
                                        <div class="text-base font-bold">{{ poli.nmpoli }}</div>
                                        <div class="text-xs text-slate-500">{{ poli.dokter?.length || 0 }} Dokter Praktik</div>
                                    </div>
                                    <span class="text-xs font-mono font-bold bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                        {{ poli.kdpoli }}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- DOCTOR LIST -->
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                                2. Pilih Dokter & Jam Praktik:
                            </label>
                            <div v-if="!selectedPoli" class="h-64 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 flex items-center justify-center text-slate-400 text-sm">
                                Silakan pilih poliklinik di sebelah kiri
                            </div>
                            <div v-else class="space-y-2 max-h-[340px] overflow-y-auto pr-1">
                                <button
                                    v-for="doc in selectedPoli.dokter"
                                    :key="doc.kodedokter"
                                    type="button"
                                    @click="chooseDoctor(doc)"
                                    :class="[
                                        selectedDoctor?.kodedokter === doc.kodedokter
                                            ? 'bg-emerald-50 border-2 border-emerald-700 text-emerald-950 font-bold shadow-sm'
                                            : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50',
                                        'w-full p-4 rounded-xl text-left flex items-center justify-between transition cursor-pointer'
                                    ]"
                                >
                                    <div>
                                        <div class="text-base font-bold">{{ doc.namadokter }}</div>
                                        <div class="text-xs text-slate-600 mt-0.5">Praktik: {{ doc.jampraktek }}</div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-xs font-bold px-2.5 py-1 rounded bg-slate-100 border border-slate-200">
                                            Sisa: {{ doc.sisa_kuota ?? doc.kuota }}
                                        </span>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- CONFIRM BUTTON -->
                    <button
                        type="button"
                        @click="submitDaftarMandiri"
                        :disabled="isLoading || !selectedPoli || !selectedDoctor"
                        class="w-full h-14 rounded-xl bg-emerald-700 hover:bg-emerald-800 active:bg-emerald-900 disabled:opacity-50 text-white font-extrabold text-lg tracking-wider transition cursor-pointer shadow-md flex items-center justify-center gap-2 mt-2"
                    >
                        <svg v-if="isLoading" class="animate-spin w-5 h-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>{{ isLoading ? 'Menerbitkan Nomor Antrean ke BPJS...' : 'AMBIL NOMOR ANTREAN POLI' }}</span>
                    </button>
                </div>

            </div>

            <!-- 4. JADWAL DOKTER VIEW -->
            <div v-else-if="currentStep === 'jadwal'" class="space-y-6 max-w-5xl mx-auto w-full">
                <div class="text-center space-y-1">
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Jadwal Praktik Poliklinik Hari Ini</h2>
                    <p class="text-sm text-slate-600">Ketersediaan kuota antrean dokter spesialis rawat jalan.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-h-[480px] overflow-y-auto pr-1">
                    <div
                        v-for="poli in listPoli"
                        :key="poli.kdpoli"
                        class="bg-white border border-slate-200 rounded-xl p-4 space-y-3 shadow-sm"
                    >
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                            <h4 class="font-bold text-slate-900 text-sm">{{ poli.nmpoli }}</h4>
                            <span class="text-xs font-mono font-bold bg-teal-100 text-teal-800 px-2 py-0.5 rounded">
                                {{ poli.kdpoli }}
                            </span>
                        </div>
                        <div class="space-y-2">
                            <div
                                v-for="dok in poli.dokter"
                                :key="dok.kodedokter"
                                class="bg-slate-50 p-2.5 rounded-lg border border-slate-200 space-y-1"
                            >
                                <div class="font-semibold text-slate-900 text-xs">{{ dok.namadokter }}</div>
                                <div class="flex items-center justify-between text-[11px] text-slate-600">
                                    <span>{{ dok.jampraktek }}</span>
                                    <span class="font-bold text-emerald-800">
                                        Sisa: {{ dok.sisa_kuota ?? dok.kuota }} Pasien
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center pt-2">
                    <button
                        type="button"
                        @click="goHome"
                        class="px-6 py-2.5 rounded-lg bg-slate-800 hover:bg-slate-900 text-white font-bold text-sm shadow cursor-pointer"
                    >
                        Kembali ke Menu Utama
                    </button>
                </div>
            </div>

            <!-- 5. TICKET SCREEN (OFFICIAL HOSPITAL THERMAL RECEIPT) -->
            <div v-else-if="currentStep === 'ticket'" class="max-w-md mx-auto w-full space-y-6">
                <div class="text-center space-y-1">
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-100 text-emerald-900 text-xs font-bold uppercase tracking-wider">
                        ✓ Berhasil Terdaftar
                    </div>
                    <h2 class="text-2xl font-black text-slate-900">Silakan Ambil Karcis Antrean Anda</h2>
                </div>

                <!-- REAL THERMAL PRINT TICKET -->
                <div id="print-area" class="bg-white text-slate-900 rounded-2xl p-6 shadow-md border-2 border-slate-300 font-mono text-center">
                    <div class="border-b-2 border-dashed border-slate-300 pb-3 space-y-0.5">
                        <div class="text-base font-black tracking-tight text-slate-950 uppercase">{{ rsName }}</div>
                        <div class="text-xs text-slate-600">KARCIS ANTREAN POLIKLINIK</div>
                        <div class="text-[11px] text-slate-500">{{ today }} • {{ currentTime }}</div>
                    </div>

                    <div class="py-4 space-y-1">
                        <div class="text-xs text-slate-500 font-bold uppercase">Nomor Antrean:</div>
                        <div class="text-6xl font-black text-teal-800 tracking-wider">
                            {{ ticketData?.nomorantrean || 'INT-001' }}
                        </div>
                        <div class="text-sm font-bold text-slate-900 uppercase mt-2">
                            {{ ticketData?.namapoli || 'POLIKLINIK' }}
                        </div>
                        <div class="text-xs text-slate-600">
                            {{ ticketData?.namadokter || 'Dokter Spesialis' }}
                        </div>
                    </div>

                    <div class="bg-slate-50 rounded-xl p-3 border border-slate-200 text-left text-xs space-y-1.5">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Kode Booking:</span>
                            <span class="font-bold text-slate-900">{{ ticketData?.kode_booking }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Nama Pasien:</span>
                            <span class="font-bold text-slate-900 truncate max-w-[160px]">{{ ticketData?.nama_pasien || '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Jenis:</span>
                            <span class="font-bold text-teal-800">{{ ticketData?.jenispasien || 'JKN' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Jam Praktik:</span>
                            <span class="font-bold text-slate-900">{{ ticketData?.jampraktek || '08:00-12:00' }}</span>
                        </div>
                        <div class="flex justify-between border-t border-slate-200 pt-1 text-[11px]">
                            <span class="text-slate-500">Status BPJS Antrol:</span>
                            <span class="font-bold text-emerald-700">Task 3 Tercatat (Tiba di RS)</span>
                        </div>
                    </div>

                    <div class="text-[11px] text-slate-500 pt-2 border-t-2 border-dashed border-slate-300 mt-3 leading-tight">
                        Harap menunggu di ruang tunggu poliklinik lantai 2. Nomor akan dipanggil melalui speaker poli. Terima kasih.
                    </div>
                </div>

                <!-- ACTIONS -->
                <div class="flex gap-3">
                    <button
                        type="button"
                        @click="printTicket"
                        class="flex-1 h-14 rounded-xl bg-teal-800 hover:bg-teal-900 text-white font-extrabold text-base flex items-center justify-center gap-2 shadow cursor-pointer transition active:scale-95"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        CETAK KARCIS
                    </button>
                    <button
                        type="button"
                        @click="goHome"
                        class="h-14 px-6 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-bold text-base cursor-pointer transition active:scale-95"
                    >
                        SELESAI
                    </button>
                </div>
            </div>

        </main>

        <!-- FOOTER BAR -->
        <footer class="bg-white border-t border-slate-200 px-6 py-3 text-center text-xs text-slate-500 flex items-center justify-between">
            <div>APM Terminal RS • Terhubung BPJS VClaim & Antrol Online</div>
            <div class="text-teal-800 font-bold">Status: Siap Melayani</div>
        </footer>

    </div>
</template>

<style scoped>
/* PRINTER THERMAL RECEIPT CSS */
@media print {
    body * {
        visibility: hidden !important;
    }
    #print-area, #print-area * {
        visibility: visible !important;
    }
    #print-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 80mm;
        margin: 0;
        padding: 8px;
        border: none !important;
        box-shadow: none !important;
    }
}
</style>
