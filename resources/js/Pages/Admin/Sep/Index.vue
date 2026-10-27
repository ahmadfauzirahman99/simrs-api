<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    antreans: {
        type: Array,
        default: () => [],
    },
    rsName: {
        type: String,
        default: 'HAURA MEDICAL',
    },
    today: {
        type: String,
        default: '',
    },
});

const searchSepNumber = ref('');
const isSearching = ref(false);
const searchError = ref('');
const currentSep = ref(null);
const showPrintModal = ref(false);
const showCreateModal = ref(false);

// Create SEP Form
const createForm = ref({
    no_kartu: '',
    nama_pasien: '',
    kodepoli: 'INT',
    namapoli: 'PENYAKIT DALAM',
    kodedokter: '101',
    namadokter: 'dr. Budi Santoso, Sp.PD',
    diagnosa: 'E11.9 - Type 2 diabetes mellitus without complications',
    no_rujukan: '',
    antrean_id: null,
});

const searchSep = async () => {
    if (!searchSepNumber.value.trim()) {
        searchError.value = 'Masukkan nomor SEP yang ingin dicari.';
        return;
    }

    isSearching.value = true;
    searchError.value = '';

    try {
        const res = await fetch('/admin/sep/search', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
            body: JSON.stringify({ no_sep: searchSepNumber.value.trim() }),
        });
        const json = await res.json();

        if (json.success && json.data) {
            currentSep.value = json.data;
            showPrintModal.value = true;
        } else {
            searchError.value = json.message || 'SEP tidak ditemukan.';
        }
    } catch (e) {
        searchError.value = 'Gagal menghubungi server VClaim BPJS.';
    } finally {
        isSearching.value = false;
    }
};

const openPrintModalForAntrean = (a) => {
    currentSep.value = {
        noSep: a.nomor_sep || ('0112R001' + dateDigits() + 'V000123'),
        tglSep: a.tanggalperiksa,
        noKartu: a.nomorkartu || '0001827364521',
        nama: a.nama_pasien || 'Pasien BPJS',
        tglLahir: '1989-08-12',
        jnsKelamin: 'Perempuan',
        poli: a.namapoli || 'PENYAKIT DALAM',
        dpjp: {
            kdDPJP: a.kodedokter,
            nmDPJP: a.namadokter || 'dr. Budi Santoso, Sp.PD',
        },
        diagnosa: 'E11.9 - Type 2 diabetes mellitus without complications',
        faskesPerujuk: 'PUSKESMAS KOTA PEKANBARU (0112U001)',
        noRujukan: a.nomorreferensi || '0112B0011026P000123',
        hakKelas: 'Kelas 1',
        jnsPeserta: 'PEKERJA PENERIMA UPAH (PPU)',
        catatan: 'Peserta rawat jalan poliklinik spesialis.',
    };
    showPrintModal.value = true;
};

const dateDigits = () => {
    const d = new Date();
    return String(d.getMonth() + 1).padStart(2, '0') + String(d.getDate()).padStart(2, '0');
};

const submitCreateSep = async () => {
    try {
        const res = await fetch('/admin/sep/generate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
            body: JSON.stringify(createForm.value),
        });
        const json = await res.json();
        if (json.success && json.data) {
            currentSep.value = json.data;
            showCreateModal.value = false;
            showPrintModal.value = true;
        }
    } catch (e) {}
};

const printSepSheet = () => {
    window.print();
};
</script>

<template>
    <Head title="Panel Lembar SEP VClaim BPJS — SIMRS Gateway" />

    <AdminLayout>
        <div class="space-y-6">

            <!-- HEADER -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-900 text-xs font-bold uppercase tracking-wider">
                            VClaim 2.0 Bridging
                        </span>
                        <span class="text-xs text-slate-400">•</span>
                        <span class="text-xs text-slate-500 font-medium">Surat Eligibilitas Peserta BPJS</span>
                    </div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">
                        Panel Kelola & Cetak Lembar SEP Resmi
                    </h1>
                    <p class="text-xs text-slate-500 mt-1 max-w-2xl leading-relaxed">
                        Cetak lembar SEP resmi berstandar BPJS Kesehatan dengan barcode, klausul persetujuan, dan tanda tangan pasien.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="showCreateModal = true"
                        class="px-4 py-2.5 rounded-xl bg-teal-800 hover:bg-teal-900 text-white font-bold text-xs shadow-sm flex items-center gap-1.5 transition active:scale-95 cursor-pointer"
                    >
                        <span>+ Terbitkan SEP Baru</span>
                    </button>
                </div>
            </div>

            <!-- SEARCH BAR -->
            <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm flex flex-col sm:flex-row items-center gap-3">
                <div class="relative flex-1 w-full">
                    <input
                        type="text"
                        v-model="searchSepNumber"
                        placeholder="Ketik Nomor SEP (Contoh: 0112R001...)..."
                        @keyup.enter="searchSep"
                        class="w-full text-xs bg-slate-50 border border-slate-300 rounded-xl py-2.5 pl-9 pr-3 text-slate-900 focus:bg-white focus:outline-none focus:border-teal-700 font-mono"
                    />
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <button
                    type="button"
                    @click="searchSep"
                    :disabled="isSearching"
                    class="h-10 px-5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs cursor-pointer transition active:scale-95 whitespace-nowrap"
                >
                    {{ isSearching ? 'Mencari...' : 'Cari di VClaim' }}
                </button>
            </div>

            <div v-if="searchError" class="p-3 bg-red-50 border border-red-200 rounded-xl text-red-800 text-xs font-semibold">
                {{ searchError }}
            </div>

            <!-- TABLE OF RECENT SEP PATIENTS -->
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-bold text-slate-900 text-sm">Daftar Pasien BPJS & Lembar SEP Hari Ini</h3>
                    <span class="text-xs text-slate-400">{{ antreans.length }} Pasien</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider">
                                <th class="p-4">Pasien & Kartu BPJS</th>
                                <th class="p-4">Poli & DPJP</th>
                                <th class="p-4">Nomor SEP</th>
                                <th class="p-4">Status Layanan</th>
                                <th class="p-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="antreans.length === 0">
                                <td colspan="5" class="p-8 text-center text-slate-400">
                                    Belum ada pasien BPJS yang terdaftar hari ini.
                                </td>
                            </tr>
                            <tr v-for="a in antreans" :key="a.id" class="hover:bg-slate-50 transition">
                                <td class="p-4">
                                    <div class="font-bold text-slate-900 text-sm">{{ a.nama_pasien }}</div>
                                    <div class="text-[11px] font-mono text-slate-500">No Kartu: {{ a.nomorkartu }} • NIK: {{ a.nik }}</div>
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-slate-900">{{ a.namapoli }}</div>
                                    <div class="text-slate-500 text-[11px]">{{ a.namadokter }}</div>
                                </td>
                                <td class="p-4">
                                    <span v-if="a.nomor_sep" class="font-mono font-bold text-blue-900 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">
                                        {{ a.nomor_sep }}
                                    </span>
                                    <span v-else class="text-amber-800 bg-amber-50 px-2 py-0.5 rounded text-[10px] font-bold border border-amber-200">
                                        Siap Terbit VClaim
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded text-[11px]">
                                        {{ a.status }}
                                    </span>
                                </td>
                                <td class="p-4 text-right">
                                    <button
                                        type="button"
                                        @click="openPrintModalForAntrean(a)"
                                        class="px-3 py-1.5 rounded-lg bg-teal-800 hover:bg-teal-900 text-white font-bold text-xs transition cursor-pointer shadow-sm"
                                    >
                                        Cetak Lembar SEP
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- MODAL PRINT RESMI SEP BPJS KESEHATAN -->
            <div v-if="showPrintModal" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
                <div class="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl space-y-4 my-8">
                    <div class="flex items-center justify-between border-b border-slate-200 pb-3 no-print">
                        <div class="font-bold text-slate-900 text-sm flex items-center gap-2">
                            <span>Pratinjau Cetak Surat Eligibilitas Peserta (SEP)</span>
                            <span class="text-xs px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold">Resmi BPJS</span>
                        </div>
                        <button
                            type="button"
                            @click="showPrintModal = false"
                            class="text-slate-400 hover:text-slate-600 font-bold text-xl cursor-pointer"
                        >
                            &times;
                        </button>
                    </div>

                    <!-- LEMBAR FISIK SEP BPJS (STANDAR NASIONAL) -->
                    <div id="print-sep-sheet" class="border border-slate-400 p-6 rounded-xl font-sans text-slate-900 bg-white text-xs leading-relaxed space-y-3">
                        <!-- KOP SURAT BPJS & RS -->
                        <div class="flex items-center justify-between border-b-2 border-slate-900 pb-2">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-emerald-700 text-white flex items-center justify-center font-black text-sm">
                                    BPJS
                                </div>
                                <div>
                                    <div class="font-black text-base text-slate-900 tracking-tight">SURAT ELIGIBILITAS PESERTA</div>
                                    <div class="font-bold text-xs text-slate-700 uppercase">{{ rsName }}</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-[10px] font-mono text-slate-500">KEMENTERIAN KESEHATAN RI</div>
                                <div class="text-xs font-bold text-slate-800">RAWAT JALAN</div>
                            </div>
                        </div>

                        <!-- BARCODE NO SEP -->
                        <div class="flex items-center justify-between py-1">
                            <div>
                                <span class="text-[11px] text-slate-500">No. SEP:</span>
                                <div class="text-base font-black font-mono tracking-widest text-slate-950">{{ currentSep?.noSep }}</div>
                            </div>
                            <div class="text-right font-mono text-xs">
                                <span class="px-3 py-1 bg-slate-100 font-black tracking-widest border border-slate-300 rounded">
                                    ||| | |||| | ||| |||| |
                                </span>
                            </div>
                        </div>

                        <!-- TABEL DATA PASIEN & RUJUKAN -->
                        <div class="grid grid-cols-2 gap-x-6 gap-y-1.5 text-xs border-t border-b border-slate-300 py-3">
                            <div class="flex justify-between"><span class="text-slate-500">Tgl. SEP:</span><span class="font-semibold">{{ currentSep?.tglSep }}</span></div>
                            <div class="flex justify-between"><span class="text-slate-500">No. Kartu:</span><span class="font-mono font-bold">{{ currentSep?.noKartu }}</span></div>

                            <div class="flex justify-between"><span class="text-slate-500">Nama Peserta:</span><span class="font-bold uppercase">{{ currentSep?.nama }}</span></div>
                            <div class="flex justify-between"><span class="text-slate-500">Peserta:</span><span class="font-semibold">{{ currentSep?.jnsPeserta }}</span></div>

                            <div class="flex justify-between"><span class="text-slate-500">Tgl. Lahir:</span><span class="font-semibold">{{ currentSep?.tglLahir }}</span></div>
                            <div class="flex justify-between"><span class="text-slate-500">Jns. Kelamin:</span><span class="font-semibold">{{ currentSep?.jnsKelamin }}</span></div>

                            <div class="flex justify-between"><span class="text-slate-500">Poli Tujuan:</span><span class="font-bold text-slate-900">{{ currentSep?.poli }}</span></div>
                            <div class="flex justify-between"><span class="text-slate-500">Hak Kelas:</span><span class="font-bold">{{ currentSep?.hakKelas }}</span></div>

                            <div class="flex justify-between"><span class="text-slate-500">DPJP Layani:</span><span class="font-bold">{{ currentSep?.dpjp?.nmDPJP }}</span></div>
                            <div class="flex justify-between"><span class="text-slate-500">Asal Rujukan:</span><span class="font-semibold">{{ currentSep?.faskesPerujuk }}</span></div>

                            <div class="col-span-2 flex justify-between border-t border-slate-200 pt-1.5">
                                <span class="text-slate-500">Diagnosa Awal:</span>
                                <span class="font-bold text-slate-900">{{ currentSep?.diagnosa }}</span>
                            </div>
                            <div class="col-span-2 flex justify-between">
                                <span class="text-slate-500">No. Rujukan:</span>
                                <span class="font-mono font-bold">{{ currentSep?.noRujukan }}</span>
                            </div>
                        </div>

                        <!-- KLAUSUL RESMI PERSETUJUAN -->
                        <div class="text-[10px] text-slate-600 leading-tight italic pt-1">
                            *Saya menyetujui BPJS Kesehatan menggunakan informasi medis dan diagnosa yang diperlukan. SEP ini bukan bukti penjaminan apabila pasien tidak memenuhi syarat penjaminan BPJS Kesehatan.
                        </div>

                        <!-- KOLOM TANDA TANGAN -->
                        <div class="grid grid-cols-2 text-center pt-4 text-xs">
                            <div class="space-y-12">
                                <div class="text-slate-600">Pasien / Keluarga Pasien,</div>
                                <div class="font-bold border-t border-slate-400 mx-10 pt-1">( {{ currentSep?.nama }} )</div>
                            </div>
                            <div class="space-y-12">
                                <div class="text-slate-600">Petugas HAURA MEDICAL,</div>
                                <div class="font-bold border-t border-slate-400 mx-10 pt-1">( Petugas Admisi / APM )</div>
                            </div>
                        </div>
                    </div>

                    <!-- ACTIONS MODAL -->
                    <div class="flex items-center justify-end gap-3 pt-2 no-print">
                        <button
                            type="button"
                            @click="showPrintModal = false"
                            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs cursor-pointer"
                        >
                            Tutup
                        </button>
                        <button
                            type="button"
                            @click="printSepSheet"
                            class="px-5 py-2.5 rounded-xl bg-teal-800 hover:bg-teal-900 text-white font-bold text-xs flex items-center gap-1.5 shadow transition cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            CETAK LEMBAR SEP SEKARANG
                        </button>
                    </div>
                </div>
            </div>

            <!-- MODAL CREATE SEP BARU -->
            <div v-if="showCreateModal" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                        <h3 class="font-bold text-slate-900 text-base">Terbitkan SEP VClaim Baru</h3>
                        <button type="button" @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600 font-bold text-xl">&times;</button>
                    </div>

                    <form @submit.prevent="submitCreateSep" class="space-y-3 text-xs">
                        <div>
                            <label class="font-bold text-slate-700">Nomor Kartu BPJS (13 Digit):</label>
                            <input type="text" v-model="createForm.no_kartu" required placeholder="0001827364521" class="w-full mt-1 p-2.5 rounded-xl border border-slate-300 font-mono" />
                        </div>
                        <div>
                            <label class="font-bold text-slate-700">Nama Pasien:</label>
                            <input type="text" v-model="createForm.nama_pasien" required placeholder="Nama Lengkap Pasien" class="w-full mt-1 p-2.5 rounded-xl border border-slate-300" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="font-bold text-slate-700">Poliklinik Tujuan:</label>
                                <input type="text" v-model="createForm.namapoli" required class="w-full mt-1 p-2.5 rounded-xl border border-slate-300" />
                            </div>
                            <div>
                                <label class="font-bold text-slate-700">Dokter DPJP:</label>
                                <input type="text" v-model="createForm.namadokter" required class="w-full mt-1 p-2.5 rounded-xl border border-slate-300" />
                            </div>
                        </div>
                        <div>
                            <label class="font-bold text-slate-700">Diagnosa Awal ICD-10:</label>
                            <input type="text" v-model="createForm.diagnosa" placeholder="E11.9 - Type 2 diabetes mellitus" class="w-full mt-1 p-2.5 rounded-xl border border-slate-300" />
                        </div>
                        <div>
                            <label class="font-bold text-slate-700">Nomor Rujukan (Opsional):</label>
                            <input type="text" v-model="createForm.no_rujukan" placeholder="0112B0011026P000123" class="w-full mt-1 p-2.5 rounded-xl border border-slate-300 font-mono" />
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                            <button type="button" @click="showCreateModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold">Batal</button>
                            <button type="submit" class="px-5 py-2 rounded-xl bg-teal-800 text-white font-bold">Terbitkan & Cetak</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>

<style scoped>
@media print {
    body * {
        visibility: hidden !important;
    }
    #print-sep-sheet, #print-sep-sheet * {
        visibility: visible !important;
    }
    #print-sep-sheet {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 16px;
        border: none !important;
    }
}
</style>
