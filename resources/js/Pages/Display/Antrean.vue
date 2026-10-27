<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';

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

const currentTime = ref('');
let clockTimer = null;
let pollTimer = null;

const updateClock = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
};

const currentCall = ref(null);
const activePoliList = ref([]);
const runningText = ref('Selamat datang di HAURA MEDICAL. Pasien dimohon memperhatikan nomor antrean dan menuju ruang periksa saat dipanggil. Siapkan KTP/Identitas dan Kartu BPJS Kesehatan Anda.');
const lastProcessedCallId = ref(null);
const audioEnabled = ref(false);

// Chime ding-dong via Web Audio API (Hospital Bell Sound)
const playChime = () => {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const now = audioCtx.currentTime;

        // Tone 1: C5 (523.25 Hz)
        const osc1 = audioCtx.createOscillator();
        const gain1 = audioCtx.createGain();
        osc1.type = 'sine';
        osc1.frequency.setValueAtTime(523.25, now);
        gain1.gain.setValueAtTime(0.35, now);
        gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.65);
        osc1.connect(gain1);
        gain1.connect(audioCtx.destination);
        osc1.start(now);
        osc1.stop(now + 0.65);

        // Tone 2: G4 (392.00 Hz)
        const osc2 = audioCtx.createOscillator();
        const gain2 = audioCtx.createGain();
        osc2.type = 'sine';
        osc2.frequency.setValueAtTime(392.00, now + 0.38);
        gain2.gain.setValueAtTime(0.35, now + 0.38);
        gain2.gain.exponentialRampToValueAtTime(0.001, now + 1.2);
        osc2.connect(gain2);
        gain2.connect(audioCtx.destination);
        osc2.start(now + 0.38);
        osc2.stop(now + 1.2);
    } catch (e) {}
};

// Text-to-Speech in Indonesian
const speakCall = (call) => {
    if (!('speechSynthesis' in window)) return;

    const codePart = call.nomorantrean.split('-')[0] || '';
    const numPart = call.nomorantrean.split('-')[1] || '';
    const spelledCode = codePart.split('').join(' ');
    const spelledNum = numPart.split('').join(' ');

    const textToSpeak = `Nomor antrean ${spelledCode}, ${spelledNum}. Silakan menuju ${call.namapoli}.`;

    window.speechSynthesis.cancel();
    const utterance = new SpeechSynthesisUtterance(textToSpeak);
    utterance.lang = 'id-ID';
    utterance.rate = 0.88;
    utterance.pitch = 1.0;

    const voices = window.speechSynthesis.getVoices();
    const idVoice = voices.find(v => v.lang.includes('id') || v.lang.includes('ID'));
    if (idVoice) utterance.voice = idVoice;

    setTimeout(() => {
        window.speechSynthesis.speak(utterance);
    }, 700);
};

// Polling data
const fetchDisplayState = async () => {
    try {
        const res = await fetch('/api/apm-api/display-state');
        const json = await res.json();
        if (json.success) {
            activePoliList.value = json.active_poli || [];
            if (json.running_text) runningText.value = json.running_text;

            const call = json.last_call;
            if (call) {
                currentCall.value = call;

                if (call.call_id && call.call_id !== lastProcessedCallId.value) {
                    lastProcessedCallId.value = call.call_id;
                    if (audioEnabled.value) {
                        playChime();
                        speakCall(call);
                    }
                }
            }
        }
    } catch (e) {}
};

const enableAudio = () => {
    audioEnabled.value = true;
    playChime();
};

onMounted(() => {
    updateClock();
    clockTimer = setInterval(updateClock, 1000);
    fetchDisplayState();
    pollTimer = setInterval(fetchDisplayState, 3000);

    if ('speechSynthesis' in window) {
        window.speechSynthesis.onvoiceschanged = () => {};
    }
});

onUnmounted(() => {
    clearInterval(clockTimer);
    clearInterval(pollTimer);
    if ('speechSynthesis' in window) window.speechSynthesis.cancel();
});
</script>

<template>
    <Head :title="`Display Antrean Poliklinik — ${rsName}`" />

    <!-- 16:9 FULLSCREEN TV SCREEN: Clean Physical Healthcare Grade -->
    <div class="min-h-screen bg-slate-100 text-slate-900 flex flex-col font-sans select-none overflow-hidden justify-between">

        <!-- TOP BAR: OFFICIAL HOSPITAL HEADER -->
        <header class="bg-[#0c2340] text-white px-8 py-3.5 flex items-center justify-between shadow-md border-b-4 border-teal-600">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-teal-600 text-white flex items-center justify-center font-black text-2xl shadow-sm border border-teal-400">
                    +
                </div>
                <div>
                    <h1 class="text-2xl font-black tracking-tight leading-none text-white uppercase">
                        {{ rsName }}
                    </h1>
                    <p class="text-xs text-teal-200 font-semibold tracking-wide mt-1">
                        Sistem Informasi Pemanggilan Antrean Poliklinik Terpadu
                    </p>
                </div>
            </div>

            <!-- RIGHT HEADER: AUDIO TOGGLE & DIGITAL CLOCK -->
            <div class="flex items-center gap-6">
                <!-- AUDIO CONTROL -->
                <button
                    v-if="!audioEnabled"
                    @click="enableAudio"
                    type="button"
                    class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs uppercase tracking-wide flex items-center gap-2 shadow transition cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                    </svg>
                    Aktifkan Suara Panggilan
                </button>
                <div v-else class="flex items-center gap-2 px-3.5 py-1.5 rounded-lg bg-teal-900/60 border border-teal-600/50 text-xs text-teal-200 font-bold">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    Suara Panggilan Aktif
                </div>

                <!-- LIVE CLOCK -->
                <div class="text-right border-l border-slate-700 pl-6">
                    <div class="text-2xl font-black font-mono tracking-wider text-white leading-none">{{ currentTime }}</div>
                    <div class="text-[11px] text-teal-200 mt-1 font-semibold">{{ today }}</div>
                </div>
            </div>
        </header>

        <!-- MAIN 2-COLUMN VIEWPORT -->
        <main class="flex-1 p-6 grid grid-cols-12 gap-6 items-stretch overflow-hidden">

            <!-- LEFT 7 COLUMNS: ACTIVE CALLED PATIENT (FOCAL POINT) -->
            <div class="col-span-12 lg:col-span-7 bg-white border-2 border-slate-300 rounded-3xl p-8 flex flex-col justify-between shadow-sm relative">
                <div>
                    <!-- STATUS CALL BAR -->
                    <div class="flex items-center justify-between border-b border-slate-200 pb-4">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-teal-600"></span>
                            <span class="text-xs font-black uppercase tracking-wider text-teal-900">
                                Panggilan Antrean Saat Ini
                            </span>
                        </div>
                        <span
                            class="px-3 py-1 rounded-full text-xs font-bold"
                            :class="currentCall ? 'bg-emerald-100 text-emerald-950 border border-emerald-300' : 'bg-slate-200 text-slate-800 border border-slate-300'"
                        >
                            {{ currentCall ? 'Sedang Dipanggil' : 'Menunggu Panggilan Dokter' }}
                        </span>
                    </div>

                    <!-- HUGE CALL NUMBER -->
                    <div class="text-center py-8 space-y-4">
                        <div class="text-xs uppercase tracking-widest font-bold text-slate-500">NOMOR ANTREAN:</div>
                        <div class="text-8xl sm:text-9xl font-black font-mono tracking-widest text-[#0c2340]">
                            {{ currentCall?.nomorantrean || '-' }}
                        </div>

                        <div class="pt-2 space-y-1">
                            <div class="text-3xl font-black text-teal-900 uppercase tracking-tight">
                                {{ currentCall?.namapoli || 'Poliklinik Siap Melayani' }}
                            </div>
                            <div class="text-lg text-slate-600 font-semibold">
                                {{ currentCall?.namadokter || 'Silakan perhatikan nomor antrean Anda' }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PATIENT FOOTNOTE / INSTRUCTIONS -->
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 flex items-center justify-between text-xs">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-teal-800 text-white flex items-center justify-center font-bold text-sm">
                            RUANG
                        </div>
                        <div>
                            <div class="text-slate-500 font-semibold">Lokasi Ruang:</div>
                            <div class="font-bold text-slate-900 text-sm">
                                {{ currentCall ? 'Ruang Pemeriksaan Poliklinik' : 'Gedung Poliklinik Terpadu Lantai 1' }}
                            </div>
                        </div>
                    </div>

                    <div class="text-right">
                        <div class="text-slate-500 font-semibold">Petunjuk Pasien:</div>
                        <div class="font-bold text-teal-900 text-sm">
                            {{ currentCall ? 'Silakan Langsung Memasuki Ruangan' : 'Menunggu nomor Anda dipanggil' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT 5 COLUMNS: ALL POLICLINICS STATUS BOARD -->
            <div class="col-span-12 lg:col-span-5 bg-white border border-slate-200 rounded-3xl p-5 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between border-b border-slate-200 pb-3 mb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-2.5 h-2.5 rounded-full bg-slate-800"></div>
                            <h3 class="text-xs font-black uppercase tracking-wider text-slate-900">
                                Monitor Antrean Poliklinik Rawat Jalan
                            </h3>
                        </div>
                        <span class="text-[11px] font-bold text-slate-500">{{ activePoliList.length }} Poli Aktif</span>
                    </div>

                    <!-- CLINIC STATUS CARDS GRID -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-2.5">
                        <div
                            v-for="poli in activePoliList"
                            :key="poli.kodepoli"
                            class="p-3 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 transition flex items-center justify-between"
                        >
                            <div class="space-y-0.5">
                                <div class="font-bold text-slate-900 text-xs flex items-center gap-2">
                                    <span>{{ poli.namapoli }}</span>
                                    <span class="text-[10px] font-semibold text-slate-400">({{ poli.ruang }})</span>
                                </div>
                                <div class="text-[11px] text-slate-600 truncate max-w-[200px]">{{ poli.namadokter }}</div>
                            </div>

                            <div class="text-right">
                                <div class="text-[10px] uppercase font-bold text-slate-400">Antrean:</div>
                                <div class="font-mono font-black text-base text-teal-900">
                                    {{ poli.nomorantrean !== '-' ? poli.nomorantrean : 'Siap' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FOOTNOTE BPJS -->
                <div class="pt-3 border-t border-slate-200 flex items-center justify-between text-[11px] text-slate-500 font-medium">
                    <span>*Terintegrasi BPJS Antrean Online (Antrol)</span>
                    <span class="font-mono font-bold text-teal-800">BPJS VClaim 2.0</span>
                </div>
            </div>

        </main>

        <!-- BOTTOM INFORMATION BAR -->
        <footer class="bg-[#0c2340] border-t-2 border-teal-600 text-white px-8 py-3 flex items-center justify-between text-xs">
            <div class="flex items-center gap-3 overflow-hidden">
                <span class="px-2.5 py-1 rounded bg-teal-600 text-white font-bold text-[11px] uppercase tracking-wider whitespace-nowrap shadow-sm">
                    INFORMASI
                </span>
                <p class="text-slate-100 font-medium truncate">
                    {{ runningText }}
                </p>
            </div>
            <div class="text-teal-200 font-semibold font-mono text-[11px] whitespace-nowrap ml-4">
                Call Center: 0812-3456-7890
            </div>
        </footer>

    </div>
</template>
