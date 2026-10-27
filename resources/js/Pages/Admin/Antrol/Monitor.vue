<script setup>
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    antreans: {
        type: Array,
        default: () => [],
    },
    selectedTanggal: {
        type: String,
        default: '',
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            checkin: 0,
            sedang_layani: 0,
            selesai: 0,
        }),
    },
});

const searchQuery = ref('');
const selectedPoliFilter = ref('ALL');
const tanggalFilter = ref(props.selectedTanggal);
const isUpdating = ref(null);

const uniquePolis = computed(() => {
    const list = new Set();
    props.antreans.forEach(a => {
        if (a.namapoli) list.add(a.namapoli);
    });
    return Array.from(list);
});

const filteredAntreans = computed(() => {
    return props.antreans.filter(a => {
        const matchesPoli = selectedPoliFilter.value === 'ALL' || a.namapoli === selectedPoliFilter.value;
        const q = searchQuery.value.toLowerCase().trim();
        const matchesQuery = !q ||
            (a.kode_booking && a.kode_booking.toLowerCase().includes(q)) ||
            (a.nomorantrean && a.nomorantrean.toLowerCase().includes(q)) ||
            (a.nama_pasien && a.nama_pasien.toLowerCase().includes(q)) ||
            (a.nik && a.nik.includes(q)) ||
            (a.nomorkartu && a.nomorkartu.includes(q));

        return matchesPoli && matchesQuery;
    });
});

const changeDate = () => {
    router.get('/admin/antrol-monitor', { tanggal: tanggalFilter.value }, { preserveState: true });
};

const triggerAdvanceTask = (antreanId, nextTaskId) => {
    isUpdating.value = `${antreanId}-${nextTaskId}`;
    router.post(`/admin/antrol-monitor/${antreanId}/advance-task`, {
        taskid: nextTaskId,
    }, {
        preserveScroll: true,
        onFinish: () => {
            isUpdating.value = null;
        },
    });
};

const taskStyle = (task, taskNum) => {
    if (!task?.waktu) {
        return 'bg-slate-100 border-slate-200 text-slate-500';
    }
    if (taskNum === 7) return 'bg-emerald-100 border-emerald-500 text-emerald-950 font-black';
    if (taskNum === 6) return 'bg-purple-100 border-purple-300 text-purple-950 font-semibold';
    if (taskNum === 4 || taskNum === 5) return 'bg-amber-100 border-amber-300 text-amber-950 font-semibold';
    if (taskNum === 3) return 'bg-teal-100 border-teal-400 text-teal-950 font-bold';
    return 'bg-emerald-100 border-emerald-300 text-emerald-950 font-semibold';
};
</script>

<template>
    <Head title="Monitoring Aliran Data Task ID (Antrol BPJS)" />

    <AdminLayout>
        <div class="space-y-6">

            <!-- HEADER BANNER -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full bg-teal-100 text-teal-800 text-xs font-bold uppercase tracking-wider">
                            BPJS Antrean Online
                        </span>
                        <span class="text-xs text-slate-400">•</span>
                        <span class="text-xs text-slate-500 font-medium">Tracking Aliran Task 1 s/d 7</span>
                    </div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">
                        Monitoring & Simulator Task ID Antrol
                    </h1>
                    <p class="text-xs text-slate-500 mt-1 max-w-2xl leading-relaxed">
                        Pantau riwayat pergerakan waktu tunggu dan waktu layan pasien dari APM, poliklinik, hingga instalasi farmasi yang disinkronkan ke BPJS Antrol secara real-time.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 bg-slate-50 border border-slate-300 rounded-xl px-3 py-1.5">
                        <span class="text-xs font-bold text-slate-500 uppercase">Tanggal:</span>
                        <input
                            type="date"
                            v-model="tanggalFilter"
                            @change="changeDate"
                            class="bg-transparent text-xs font-bold text-slate-800 focus:outline-none cursor-pointer"
                        />
                    </div>
                    <Link
                        href="/apm"
                        target="_blank"
                        class="px-4 py-2 rounded-xl bg-teal-700 hover:bg-teal-800 text-white font-bold text-xs shadow-sm flex items-center gap-1.5 transition active:scale-95"
                    >
                        <span>Buka APM Kiosk</span>
                        <span>&rarr;</span>
                    </Link>
                </div>
            </div>

            <!-- STATS CARDS -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Antrean Hari Ini</div>
                    <div class="text-3xl font-black text-slate-900 mt-1">{{ stats.total }}</div>
                    <div class="text-[11px] text-slate-500 mt-0.5">Pasien terdaftar di sistem</div>
                </div>
                <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
                    <div class="text-xs font-bold uppercase tracking-wider text-teal-600">Check-In / Tiba (Task 3)</div>
                    <div class="text-3xl font-black text-teal-800 mt-1">{{ stats.checkin }}</div>
                    <div class="text-[11px] text-teal-600 mt-0.5">Sudah tiba di faskes</div>
                </div>
                <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
                    <div class="text-xs font-bold uppercase tracking-wider text-amber-600">Sedang Di Poli (Task 4)</div>
                    <div class="text-3xl font-black text-amber-700 mt-1">{{ stats.sedang_layani }}</div>
                    <div class="text-[11px] text-amber-600 mt-0.5">Pemeriksaan dokter</div>
                </div>
                <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
                    <div class="text-xs font-bold uppercase tracking-wider text-emerald-600">Selesai (Task 7)</div>
                    <div class="text-3xl font-black text-emerald-700 mt-1">{{ stats.selesai }}</div>
                    <div class="text-[11px] text-emerald-600 mt-0.5">Obat sudah diserahkan</div>
                </div>
            </div>

            <!-- CONTROLS & FILTER -->
            <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="relative w-full sm:w-80">
                    <input
                        type="text"
                        v-model="searchQuery"
                        placeholder="Cari kode booking / antrean / pasien..."
                        class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl py-2.5 pl-9 pr-3 text-slate-900 focus:bg-white focus:outline-none focus:border-teal-700"
                    />
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <span class="text-xs font-bold text-slate-500 whitespace-nowrap">Filter Poli:</span>
                    <select
                        v-model="selectedPoliFilter"
                        class="text-xs bg-slate-50 border border-slate-300 rounded-xl py-2 px-3 text-slate-800 font-semibold focus:outline-none focus:bg-white"
                    >
                        <option value="ALL">Semua Poliklinik</option>
                        <option v-for="p in uniquePolis" :key="p" :value="p">{{ p }}</option>
                    </select>
                </div>
            </div>

            <!-- PATIENT TASK ID PIPELINE TABLE -->
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider">
                                <th class="p-4">Pasien & Antrean</th>
                                <th class="p-4">Poli & DPJP</th>
                                <th class="p-4 min-w-[480px]">Pipeline Aliran Task ID (1 s/d 7)</th>
                                <th class="p-4 text-center">Durasi Tunggu</th>
                                <th class="p-4 text-right">Aksi Simulator</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="filteredAntreans.length === 0">
                                <td colspan="5" class="p-8 text-center text-slate-400">
                                    Belum ada antrean terdaftar pada tanggal ini.
                                </td>
                            </tr>
                            <tr
                                v-for="a in filteredAntreans"
                                :key="a.id"
                                class="hover:bg-slate-50/70 transition"
                            >
                                <!-- PASIEN -->
                                <td class="p-4 align-top space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-black text-base text-teal-800 font-mono">{{ a.nomorantrean }}</span>
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                            {{ a.sumber }}
                                        </span>
                                    </div>
                                    <div class="font-bold text-slate-900 text-sm">{{ a.nama_pasien || '-' }}</div>
                                    <div class="text-[11px] font-mono text-slate-500">
                                        Booking: <span class="font-semibold text-slate-700">{{ a.kode_booking }}</span>
                                    </div>
                                </td>

                                <!-- POLI & DOKTER -->
                                <td class="p-4 align-top space-y-1">
                                    <div class="font-bold text-slate-900">{{ a.namapoli }}</div>
                                    <div class="text-slate-600">{{ a.namadokter }}</div>
                                    <div class="text-[11px] text-slate-400">{{ a.jampraktek }}</div>
                                </td>

                                <!-- PIPELINE TASK ID (1-7) -->
                                <td class="p-4 align-top">
                                    <div class="grid grid-cols-7 gap-1.5 text-center">
                                        <!-- TASK 1 -->
                                        <div :class="taskStyle(a.tasks.task1, 1)" class="p-2 rounded-lg border flex flex-col justify-between min-h-[58px]">
                                            <div class="text-[9px] font-bold uppercase">T1. Ambil</div>
                                            <div class="font-mono text-[10px] font-black mt-1">{{ a.tasks.task1.time_str || '-' }}</div>
                                        </div>
                                        <!-- TASK 2 -->
                                        <div :class="taskStyle(a.tasks.task2, 2)" class="p-2 rounded-lg border flex flex-col justify-between min-h-[58px]">
                                            <div class="text-[9px] font-bold uppercase">T2. Loket</div>
                                            <div class="font-mono text-[10px] font-black mt-1">{{ a.tasks.task2.time_str || '-' }}</div>
                                        </div>
                                        <!-- TASK 3 -->
                                        <div :class="taskStyle(a.tasks.task3, 3)" class="p-2 rounded-lg border flex flex-col justify-between min-h-[58px]">
                                            <div class="text-[9px] font-bold uppercase">T3. CheckIn</div>
                                            <div class="font-mono text-[10px] font-black mt-1">{{ a.tasks.task3.time_str || '-' }}</div>
                                        </div>
                                        <!-- TASK 4 -->
                                        <div :class="taskStyle(a.tasks.task4, 4)" class="p-2 rounded-lg border flex flex-col justify-between min-h-[58px]">
                                            <div class="text-[9px] font-bold uppercase">T4. Dokter</div>
                                            <div class="font-mono text-[10px] font-black mt-1">{{ a.tasks.task4.time_str || '-' }}</div>
                                        </div>
                                        <!-- TASK 5 -->
                                        <div :class="taskStyle(a.tasks.task5, 5)" class="p-2 rounded-lg border flex flex-col justify-between min-h-[58px]">
                                            <div class="text-[9px] font-bold uppercase">T5. Selesai</div>
                                            <div class="font-mono text-[10px] font-black mt-1">{{ a.tasks.task5.time_str || '-' }}</div>
                                        </div>
                                        <!-- TASK 6 -->
                                        <div :class="taskStyle(a.tasks.task6, 6)" class="p-2 rounded-lg border flex flex-col justify-between min-h-[58px]">
                                            <div class="text-[9px] font-bold uppercase">T6. Farmasi</div>
                                            <div class="font-mono text-[10px] font-black mt-1">{{ a.tasks.task6.time_str || '-' }}</div>
                                        </div>
                                        <!-- TASK 7 -->
                                        <div :class="taskStyle(a.tasks.task7, 7)" class="p-2 rounded-lg border flex flex-col justify-between min-h-[58px]">
                                            <div class="text-[9px] font-bold uppercase">T7. Obat OK</div>
                                            <div class="font-mono text-[10px] font-black mt-1">{{ a.tasks.task7.time_str || '-' }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- DURASI / MUTU SPM -->
                                <td class="p-4 align-top text-center space-y-1">
                                    <div v-if="a.durasi.tunggu_poli !== null" class="text-[11px] text-slate-600">
                                        Tunggu Poli: <span class="font-bold text-slate-900">{{ a.durasi.tunggu_poli }} m</span>
                                    </div>
                                    <div v-if="a.durasi.layan_dokter !== null" class="text-[11px] text-slate-600">
                                        Dokter: <span class="font-bold text-slate-900">{{ a.durasi.layan_dokter }} m</span>
                                    </div>
                                    <div v-if="a.durasi.total_waktu !== null" class="text-[11px] font-bold text-teal-800">
                                        Total: {{ a.durasi.total_waktu }} m
                                    </div>
                                    <div v-else class="text-[11px] text-slate-400 italic">
                                        Sedang proses
                                    </div>
                                </td>

                                <!-- SIMULATOR AKSI PERJALANAN TASK -->
                                <td class="p-4 align-top text-right space-y-1.5">
                                    <!-- Jika T3 selesai tapi T4 belum: Panggil Dokter -->
                                    <button
                                        v-if="a.tasks.task3.waktu && !a.tasks.task4.waktu"
                                        type="button"
                                        @click="triggerAdvanceTask(a.id, 4)"
                                        :disabled="isUpdating === `${a.id}-4`"
                                        class="px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs shadow-sm transition active:scale-95 cursor-pointer whitespace-nowrap"
                                    >
                                        Panggil Dokter (Task 4)
                                    </button>

                                    <!-- Jika T4 selesai tapi T5 belum: Selesai Dokter -->
                                    <button
                                        v-else-if="a.tasks.task4.waktu && !a.tasks.task5.waktu"
                                        type="button"
                                        @click="triggerAdvanceTask(a.id, 5)"
                                        :disabled="isUpdating === `${a.id}-5`"
                                        class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-sm transition active:scale-95 cursor-pointer whitespace-nowrap"
                                    >
                                        Selesai Resep (Task 5)
                                    </button>

                                    <!-- Jika T5 selesai tapi T6 belum: Mulai Farmasi -->
                                    <button
                                        v-else-if="a.tasks.task5.waktu && !a.tasks.task6.waktu"
                                        type="button"
                                        @click="triggerAdvanceTask(a.id, 6)"
                                        :disabled="isUpdating === `${a.id}-6`"
                                        class="px-3 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-sm transition active:scale-95 cursor-pointer whitespace-nowrap"
                                    >
                                        Racik Obat (Task 6)
                                    </button>

                                    <!-- Jika T6 selesai tapi T7 belum: Selesai Obat -->
                                    <button
                                        v-else-if="a.tasks.task6.waktu && !a.tasks.task7.waktu"
                                        type="button"
                                        @click="triggerAdvanceTask(a.id, 7)"
                                        :disabled="isUpdating === `${a.id}-7`"
                                        class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm transition active:scale-95 cursor-pointer whitespace-nowrap"
                                    >
                                        Serahkan Obat (Task 7)
                                    </button>

                                    <!-- Jika T7 selesai -->
                                    <div v-else-if="a.tasks.task7.waktu" class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700">
                                        <span>Tuntas Melayani</span>
                                        <span>✓</span>
                                    </div>

                                    <!-- Belum check in -->
                                    <div v-else class="text-[11px] text-slate-400 italic">
                                        Menunggu Check-In APM
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
