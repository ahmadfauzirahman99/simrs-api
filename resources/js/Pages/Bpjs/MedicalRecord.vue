<script setup>
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, reactive, ref } from 'vue';
import AdminLayout from '../../Layouts/AdminLayout.vue';

const stringifyExample = (field) => {
    if (field.type !== 'array' && field.type !== 'object') {
        return field.example ?? '';
    }

    try {
        return JSON.stringify(field.example ?? (field.type === 'array' ? [] : {}), null, 2);
    } catch {
        return field.type === 'array' ? '[]' : '{}';
    }
};

const props = defineProps({
    history: {
        type: Array,
        default: () => [],
    },
    resourceBlueprints: {
        type: Array,
        default: () => [],
    },
    historyReady: {
        type: Boolean,
        default: true,
    },
    samplePath: {
        type: String,
        default: '',
    },
    rsCode: {
        type: String,
        default: '',
    },
    kemenkesCode: {
        type: String,
        default: '',
    },
    providerCode: {
        type: String,
        default: '',
    },
});

const form = reactive({
    no_sep: '0082R0120925V000002',
    jns_pelayanan: '1',
    bulan: '09',
    tahun: '2025',
});

const loading = ref(false);
const actionMode = ref('preview');
const copiedState = ref('');
const currentResult = ref(null);
const historyItems = ref(props.history);
const selectedHistoryId = ref(props.history[0]?.id ?? null);
const stagedResources = ref([]);

const selectedHistory = computed(
    () => historyItems.value.find((item) => item.id === selectedHistoryId.value) ?? historyItems.value[0] ?? null,
);
const selectedBlueprintKey = ref(props.resourceBlueprints[0]?.resource ?? 'patient');
const selectedBlueprint = computed(
    () => props.resourceBlueprints.find((item) => item.resource === selectedBlueprintKey.value) ?? props.resourceBlueprints[0] ?? null,
);
const blueprintFormState = reactive(
    Object.fromEntries(
        props.resourceBlueprints.map((item) => [
            item.resource,
            Object.fromEntries((item.required_fields ?? []).map((field) => [field.path, stringifyExample(field)])),
        ]),
    ),
);

const currentSnapshotEntries = computed(() => {
    const snapshot = currentResult.value?.bundle_snapshot;

    if (!snapshot) {
        return [];
    }

    return Object.entries(snapshot.resource_summary ?? {});
});

const currentDataMr = computed(() => currentResult.value?.request_payload?.request?.dataMR ?? '');

const selectedHistorySnapshotEntries = computed(() => {
    const snapshot = selectedHistory.value?.bundle_snapshot;

    if (!snapshot) {
        return [];
    }

    return Object.entries(snapshot.resource_summary ?? {});
});

const selectedBlueprintForm = computed(() => blueprintFormState[selectedBlueprintKey.value] ?? {});
const bundleBlueprint = computed(
    () => props.resourceBlueprints.find((item) => item.resource === 'bundle') ?? null,
);

const clone = (value) => JSON.parse(JSON.stringify(value));

const setNestedValue = (target, path, value) => {
    const segments = path.split('.');
    let cursor = target;

    segments.forEach((segment, index) => {
        const match = segment.match(/^([^\[]+)(?:\[(\d+)\])?$/);
        const key = match?.[1] ?? segment;
        const arrayIndex = match?.[2] !== undefined ? Number(match[2]) : null;
        const isLast = index === segments.length - 1;

        if (arrayIndex === null) {
            if (isLast) {
                cursor[key] = value;
                return;
            }

            if (typeof cursor[key] !== 'object' || cursor[key] === null) {
                cursor[key] = {};
            }

            cursor = cursor[key];
            return;
        }

        if (!Array.isArray(cursor[key])) {
            cursor[key] = [];
        }

        if (cursor[key][arrayIndex] === undefined || cursor[key][arrayIndex] === null || typeof cursor[key][arrayIndex] !== 'object') {
            cursor[key][arrayIndex] = {};
        }

        if (isLast) {
            cursor[key][arrayIndex] = value;
            return;
        }

        cursor = cursor[key][arrayIndex];
    });
};

const normalizedFieldValue = (field, rawValue) => {
    if (rawValue === '' || rawValue === null || rawValue === undefined) {
        return rawValue;
    }

    if (field.type === 'array' || field.type === 'object') {
        try {
            return JSON.parse(rawValue);
        } catch {
            return rawValue;
        }
    }

    if (field.type === 'number') {
        return Number(rawValue);
    }

    if (field.type === 'boolean') {
        if (rawValue === true || rawValue === 'true' || rawValue === '1') {
            return true;
        }

        if (rawValue === false || rawValue === 'false' || rawValue === '0') {
            return false;
        }
    }

    return rawValue;
};

const selectedBlueprintGenerated = computed(() => {
    if (!selectedBlueprint.value) {
        return null;
    }

    const template = clone(selectedBlueprint.value.template ?? {});

    (selectedBlueprint.value.required_fields ?? []).forEach((field) => {
        const rawValue = selectedBlueprintForm.value[field.path];

        if (rawValue === '' || rawValue === undefined) {
            return;
        }

        setNestedValue(template, field.path, normalizedFieldValue(field, rawValue));
    });

    return template;
});

const stagedBundle = computed(() => {
    const template = clone(bundleBlueprint.value?.template ?? {
        resourceType: 'Bundle',
        type: 'Document',
        entry: [],
    });

    template.entry = stagedResources.value.map((item) => ({
        resource: item.resource,
    }));

    return template;
});

const stagedSummary = computed(() =>
    stagedResources.value.reduce((summary, item) => {
        const type = item.resource?.resourceType ?? 'Unknown';
        summary[type] = (summary[type] ?? 0) + 1;
        return summary;
    }, {}),
);

const inputType = (field) => {
    if (field.type === 'date') {
        return 'date';
    }

    if (field.type === 'datetime') {
        return 'datetime-local';
    }

    if (field.type === 'number') {
        return 'number';
    }

    return 'text';
};

const generateUuid = () => {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }

    return `${Date.now()}-${Math.random().toString(16).slice(2)}-${Math.random().toString(16).slice(2)}`;
};

const cleanCode = (value) => String(value ?? '').replace(/[^A-Za-z0-9]/g, '');

const generateResourceId = () => {
    if (!selectedBlueprint.value) {
        return;
    }

    const fields = selectedBlueprint.value.required_fields ?? [];
    const hasIdField = fields.some((field) => field.path === 'id');

    if (!hasIdField) {
        return;
    }

    const rsCode = cleanCode(props.rsCode || props.providerCode);
    const kemenkesCode = cleanCode(props.kemenkesCode || props.rsCode || props.providerCode);
    const jnsPelayanan = cleanCode(form.jns_pelayanan);

    blueprintFormState[selectedBlueprint.value.resource].id = `${rsCode}${kemenkesCode}${jnsPelayanan}${generateUuid()}`;
};

const addToBundle = () => {
    if (!selectedBlueprintGenerated.value) {
        return;
    }

    stagedResources.value.unshift({
        localId: `${selectedBlueprintKey.value}-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
        type: selectedBlueprint.value?.label ?? selectedBlueprintKey.value,
        resource: clone(selectedBlueprintGenerated.value),
    });
};

const removeStagedResource = (localId) => {
    stagedResources.value = stagedResources.value.filter((item) => item.localId !== localId);
};

const clearStagedBundle = () => {
    stagedResources.value = [];
};

const pretty = (value) => {
    if (typeof value === 'string') {
        return value;
    }

    try {
        return JSON.stringify(value, null, 2);
    } catch {
        return String(value);
    }
};

const formatDate = (value) => {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
};

const copyText = async (label, value) => {
    try {
        await navigator.clipboard.writeText(typeof value === 'string' ? value : pretty(value));
        copiedState.value = `${label} copied`;
        window.setTimeout(() => {
            copiedState.value = '';
        }, 1800);
    } catch {
        copiedState.value = `Gagal copy ${label.toLowerCase()}`;
    }
};

const statusBadgeClass = (status) => {
    if (status === 'sent') {
        return 'bg-emerald-100 text-emerald-800';
    }

    if (status === 'failed') {
        return 'bg-rose-100 text-rose-800';
    }

    if (status === 'generated') {
        return 'bg-amber-100 text-amber-800';
    }

    return 'bg-slate-100 text-slate-700';
};

const refreshHistory = async () => {
    const response = await axios.get('/api/v1/bpjs/rekam-medis/history');
    historyItems.value = response.data?.response?.items ?? [];

    if (historyItems.value.length && !historyItems.value.some((item) => item.id === selectedHistoryId.value)) {
        selectedHistoryId.value = historyItems.value[0].id;
    }
};

const submit = async (mode, useStagingBundle = false) => {
    loading.value = true;
    actionMode.value = mode;

    try {
        const payload = {
            ...form,
            ...(useStagingBundle && stagedResources.value.length ? { bundle: stagedBundle.value } : {}),
        };

        const response = await axios.post(
            mode === 'preview' ? '/api/v1/bpjs/rekam-medis/preview' : '/api/v1/bpjs/rekam-medis/insert',
            payload,
        );

        currentResult.value = response.data?.response ?? null;
        selectedHistoryId.value = currentResult.value?.job_id ?? selectedHistoryId.value;
        await refreshHistory();
    } catch (err) {
        currentResult.value = {
            error: err.response?.data ?? {
                metaData: {
                    code: '500',
                    message: 'Gagal memproses medical record BPJS.',
                },
            },
        };
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <Head title="BPJS Medical Record" />

    <AdminLayout
        title="BPJS Medical Record Console"
        subtitle="Preview bundle, hasil dataMR, dan histori pengiriman rekam medis BPJS dalam satu panel audit."
    >
        <div class="grid gap-6 xl:grid-cols-[420px,minmax(0,1fr)]">
            <section class="space-y-6">
                <article class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)]">
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-[var(--app-accent)]">Generator</p>
                    <h2 class="mt-3 text-2xl font-semibold text-slate-900">BPJS Rekam Medis</h2>
                    <p class="mt-2 text-sm text-slate-600">
                        Gunakan sample bundle untuk preview payload dan kirim live ke endpoint Trust Mark BPJS.
                    </p>

                    <div class="mt-5 grid gap-4">
                        <label class="text-sm font-medium text-slate-700">
                            No. SEP
                            <input v-model="form.no_sep" type="text" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-[var(--app-accent)]" />
                        </label>

                        <label class="text-sm font-medium text-slate-700">
                            Jenis Pelayanan
                            <select v-model="form.jns_pelayanan" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-[var(--app-accent)]">
                                <option value="1">1 - Rawat Inap</option>
                                <option value="2">2 - Rawat Jalan</option>
                            </select>
                        </label>

                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="text-sm font-medium text-slate-700">
                                Bulan
                                <input v-model="form.bulan" type="text" maxlength="2" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-[var(--app-accent)]" />
                            </label>
                            <label class="text-sm font-medium text-slate-700">
                                Tahun
                                <input v-model="form.tahun" type="text" maxlength="4" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-[var(--app-accent)]" />
                            </label>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3 md:grid-cols-2">
                        <button
                            type="button"
                            class="rounded-2xl bg-[var(--app-accent)] px-4 py-3 text-sm font-semibold text-white transition hover:brightness-95 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="loading"
                            @click="submit('preview')"
                        >
                            {{ loading && actionMode === 'preview' ? 'Membuat Preview...' : 'Preview Bundle + dataMR' }}
                        </button>
                        <button
                            type="button"
                            class="rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="loading"
                            @click="submit('insert')"
                        >
                            {{ loading && actionMode === 'insert' ? 'Mengirim Live...' : 'Insert Live BPJS' }}
                        </button>
                    </div>

                    <div class="mt-5 grid gap-3">
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Sample Path</p>
                            <p class="mt-2 break-all text-sm text-slate-700">{{ samplePath || '-' }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Provider Code</p>
                            <p class="mt-2 text-sm text-slate-700">{{ providerCode || '-' }}</p>
                        </div>
                    </div>

                    <p v-if="copiedState" class="mt-4 text-sm font-medium text-emerald-700">{{ copiedState }}</p>
                </article>

                <article class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)]">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-[var(--app-accent)]">History</p>
                            <h3 class="mt-2 text-xl font-semibold text-slate-900">Audit Pengiriman</h3>
                        </div>
                        <button type="button" class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50" @click="refreshHistory">
                            Refresh
                        </button>
                    </div>

                    <div class="mt-5 space-y-3 max-h-[900px] overflow-y-auto pr-1">
                        <div v-if="!historyReady" class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800">
                            Tabel histori belum tersedia. Jalankan migrasi agar preview dan insert bisa otomatis tersimpan ke audit log.
                        </div>

                        <button
                            v-for="item in historyItems"
                            :key="item.id"
                            type="button"
                            class="w-full rounded-2xl border px-4 py-4 text-left transition"
                            :class="selectedHistoryId === item.id ? 'border-[var(--app-accent)] bg-emerald-50' : 'border-slate-200 bg-white hover:bg-slate-50'"
                            @click="selectedHistoryId = item.id"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">Job #{{ item.id }}</p>
                                    <p class="mt-1 text-sm text-slate-600">{{ item.sep_no }}</p>
                                </div>
                                <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="statusBadgeClass(item.status)">
                                    {{ item.status }}
                                </span>
                            </div>
                            <div class="mt-3 grid gap-2 text-xs text-slate-500 md:grid-cols-2">
                                <p>{{ item.jns_pelayanan_label }}</p>
                                <p>{{ item.bulan }}/{{ item.tahun }}</p>
                                <p>{{ formatDate(item.created_at) }}</p>
                                <p>Kode: {{ item.payload?.bpjs_response_code ?? '-' }}</p>
                            </div>
                        </button>

                        <div v-if="!historyItems.length" class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">
                            Belum ada histori preview atau pengiriman medical record.
                        </div>
                    </div>
                </article>

                <article class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)]">
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-[var(--app-accent)]">Resource Blueprint</p>
                    <h3 class="mt-2 text-xl font-semibold text-slate-900">Field Wajib per Resource</h3>
                    <p class="mt-2 text-sm text-slate-600">
                        Pilih resource untuk melihat field wajib dan template dasar yang bisa langsung dipakai saat generate bundle.
                    </p>

                    <select
                        v-model="selectedBlueprintKey"
                        class="mt-4 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-[var(--app-accent)]"
                    >
                        <option v-for="item in resourceBlueprints" :key="item.resource" :value="item.resource">
                            {{ item.label }}
                        </option>
                    </select>

                    <div v-if="selectedBlueprint" class="mt-5 space-y-4">
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-900">{{ selectedBlueprint.label }}</p>
                            <p class="mt-2 text-sm text-slate-600">{{ selectedBlueprint.description }}</p>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="field in selectedBlueprint.required_fields"
                                :key="field.path"
                                class="rounded-2xl border border-slate-200 p-4"
                            >
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-slate-900">{{ field.path }}</p>
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ field.type }}</span>
                                </div>
                                <p class="mt-2 text-sm text-slate-600">{{ field.description }}</p>
                                <p class="mt-2 text-xs text-slate-500">Contoh: {{ pretty(field.example) }}</p>
                            </div>
                        </div>

                        <div class="rounded-3xl border border-slate-200 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">Form Isian {{ selectedBlueprint.label }}</p>
                                    <p class="mt-1 text-sm text-slate-600">Isi field wajib di sini, lalu salin hasil JSON resource yang terbentuk.</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        Format ID otomatis:
                                        <span class="font-semibold text-slate-700">kodeRS + kodeKemenkes + jnsPelayanan + UUID</span>
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                        @click="generateResourceId"
                                    >
                                        Generate ID Otomatis
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                        @click="copyText(`${selectedBlueprint.label} JSON`, selectedBlueprintGenerated)"
                                    >
                                        Copy JSON
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-2xl bg-[var(--app-accent)] px-4 py-2 text-sm font-semibold text-white transition hover:brightness-95"
                                        @click="addToBundle"
                                    >
                                        Tambah ke Bundle
                                    </button>
                                </div>
                            </div>

                            <div class="mt-4 grid gap-4">
                                <div v-for="field in selectedBlueprint.required_fields" :key="`${selectedBlueprint.resource}-${field.path}`">
                                    <label class="text-sm font-medium text-slate-700">
                                        {{ field.path }}
                                        <textarea
                                            v-if="field.type === 'array' || field.type === 'object'"
                                            v-model="blueprintFormState[selectedBlueprint.resource][field.path]"
                                            rows="3"
                                            class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-[var(--app-accent)]"
                                        />
                                        <select
                                            v-else-if="field.type === 'boolean'"
                                            v-model="blueprintFormState[selectedBlueprint.resource][field.path]"
                                            class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-[var(--app-accent)]"
                                        >
                                            <option value="true">true</option>
                                            <option value="false">false</option>
                                        </select>
                                        <input
                                            v-else
                                            v-model="blueprintFormState[selectedBlueprint.resource][field.path]"
                                            :type="inputType(field)"
                                            class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none transition focus:border-[var(--app-accent)]"
                                        />
                                    </label>
                                </div>
                            </div>

                            <div class="mt-5">
                                <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-500">Generated Resource</p>
                                <pre class="mt-3 overflow-x-auto rounded-3xl bg-slate-950 p-5 text-xs text-slate-100">{{ pretty(selectedBlueprintGenerated) }}</pre>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                            @click="copyText(`${selectedBlueprint.label} Template`, selectedBlueprint.template)"
                        >
                            Copy Template
                        </button>
                    </div>
                </article>

                <article class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)]">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-[var(--app-accent)]">Staging Bundle</p>
                            <h3 class="mt-2 text-xl font-semibold text-slate-900">Resource yang Sudah Dikumpulkan</h3>
                            <p class="mt-1 text-sm text-slate-600">Tambah resource dari form di atas, lalu susun bundle final sebelum dipakai ke generator BPJS.</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                @click="copyText('Bundle JSON', stagedBundle)"
                            >
                                Copy Bundle
                            </button>
                            <button
                                type="button"
                                class="rounded-2xl border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-50"
                                @click="clearStagedBundle"
                            >
                                Kosongkan
                            </button>
                            <button
                                type="button"
                                class="rounded-2xl bg-[var(--app-accent)] px-4 py-2 text-sm font-semibold text-white transition hover:brightness-95 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="loading || !stagedResources.length"
                                @click="submit('preview', true)"
                            >
                                Preview dari Staging Bundle
                            </button>
                            <button
                                type="button"
                                class="rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="loading || !stagedResources.length"
                                @click="submit('insert', true)"
                            >
                                Insert Live dari Staging
                            </button>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-5 xl:grid-cols-[360px,minmax(0,1fr)]">
                        <div>
                            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-1">
                                <div
                                    v-for="(total, type) in stagedSummary"
                                    :key="type"
                                    class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3 text-sm"
                                >
                                    <span class="font-medium text-slate-700">{{ type }}</span>
                                    <span class="font-semibold text-slate-900">{{ total }}</span>
                                </div>
                            </div>

                            <div class="mt-4 space-y-3 max-h-[620px] overflow-y-auto pr-1">
                                <div
                                    v-for="item in stagedResources"
                                    :key="item.localId"
                                    class="rounded-2xl border border-slate-200 p-4"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900">
                                                {{ item.resource.resourceType }}{{ item.resource.id ? ` · ${item.resource.id}` : '' }}
                                            </p>
                                            <p class="mt-1 text-xs text-slate-500">{{ item.type }}</p>
                                        </div>
                                        <div class="flex gap-2">
                                            <button
                                                type="button"
                                                class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                                @click="copyText(`${item.resource.resourceType} Resource`, item.resource)"
                                            >
                                                Copy
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded-xl border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-50"
                                                @click="removeStagedResource(item.localId)"
                                            >
                                                Hapus
                                            </button>
                                        </div>
                                    </div>
                                    <pre class="mt-3 max-h-[220px] overflow-auto rounded-2xl bg-slate-950 p-4 text-xs text-slate-100">{{ pretty(item.resource) }}</pre>
                                </div>

                                <div v-if="!stagedResources.length" class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">
                                    Belum ada resource di staging. Pilih blueprint, isi form, lalu klik <span class="font-semibold text-slate-700">Tambah ke Bundle</span>.
                                </div>
                            </div>
                        </div>

                        <div class="rounded-3xl border border-slate-200 p-5">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-500">Generated Bundle</p>
                                    <p class="mt-1 text-sm text-slate-600">Preview bundle final dari resource yang sudah dikumpulkan.</p>
                                </div>
                                <p class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                    {{ stagedResources.length }} resource
                                </p>
                            </div>
                            <pre class="mt-4 max-h-[760px] overflow-auto rounded-3xl bg-slate-950 p-5 text-xs text-slate-100">{{ pretty(stagedBundle) }}</pre>
                        </div>
                    </div>
                </article>
            </section>

            <section class="space-y-6">
                <article class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)]">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-[var(--app-accent)]">Current Result</p>
                            <h3 class="mt-2 text-xl font-semibold text-slate-900">Preview dan Request Final</h3>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button
                                v-if="currentResult?.request_payload"
                                type="button"
                                class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                @click="copyText('Payload', currentResult.request_payload)"
                            >
                                Copy Payload
                            </button>
                            <button
                                v-if="currentDataMr"
                                type="button"
                                class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                @click="copyText('dataMR', currentDataMr)"
                            >
                                Copy dataMR
                            </button>
                        </div>
                    </div>

                    <div v-if="currentResult?.error" class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 p-4">
                        <p class="text-sm font-semibold text-rose-800">{{ currentResult.error.metaData?.message ?? 'Gagal memproses request.' }}</p>
                        <pre class="mt-3 overflow-x-auto rounded-2xl bg-slate-950 p-4 text-xs text-slate-100">{{ pretty(currentResult.error) }}</pre>
                    </div>

                    <div v-else-if="currentResult" class="mt-5 space-y-5">
                        <div class="grid gap-4 md:grid-cols-4">
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Job ID</p>
                                <p class="mt-2 text-lg font-semibold text-slate-900">{{ currentResult.job_id ?? '-' }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Payload ID</p>
                                <p class="mt-2 text-lg font-semibold text-slate-900">{{ currentResult.payload_id ?? '-' }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Encrypted Length</p>
                                <p class="mt-2 text-lg font-semibold text-slate-900">{{ currentResult.encoding?.encrypted_payload_length ?? currentDataMr.length }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Gzip Base64 Length</p>
                                <p class="mt-2 text-lg font-semibold text-slate-900">{{ currentResult.encoding?.gzip_payload_base64_length ?? '-' }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Bundle Source</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">{{ currentResult.source?.bundle_source ?? '-' }}</p>
                            </div>
                        </div>

                        <div class="grid gap-5 xl:grid-cols-[380px,minmax(0,1fr)]">
                            <div class="rounded-3xl border border-slate-200 p-5">
                                <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-500">Bundle Snapshot</p>
                                <div class="mt-4 space-y-3 text-sm text-slate-600">
                                    <p><span class="font-semibold text-slate-900">Bundle ID:</span> {{ currentResult.bundle_snapshot?.bundle_id ?? '-' }}</p>
                                    <p><span class="font-semibold text-slate-900">Type:</span> {{ currentResult.bundle_snapshot?.bundle_type ?? '-' }}</p>
                                    <p><span class="font-semibold text-slate-900">Entry Count:</span> {{ currentResult.bundle_snapshot?.entry_count ?? '-' }}</p>
                                </div>
                                <div class="mt-5 space-y-2">
                                    <div
                                        v-for="[name, total] in currentSnapshotEntries"
                                        :key="name"
                                        class="flex items-center justify-between rounded-2xl bg-slate-50 px-3 py-2 text-sm"
                                    >
                                        <span class="font-medium text-slate-700">{{ name }}</span>
                                        <span class="font-semibold text-slate-900">{{ total }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-5">
                                <div class="rounded-3xl border border-slate-200 p-5">
                                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-500">Request Payload</p>
                                    <pre class="mt-4 max-h-[320px] overflow-auto whitespace-pre-wrap break-all rounded-3xl bg-slate-950 p-5 text-xs text-slate-100">{{ pretty(currentResult.request_payload) }}</pre>
                                </div>

                                <div class="rounded-3xl border border-slate-200 p-5">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-500">dataMR Preview</p>
                                        <p class="text-xs text-slate-500">{{ currentDataMr.length }} chars</p>
                                    </div>
                                    <pre class="mt-4 max-h-[220px] overflow-auto whitespace-pre-wrap break-all rounded-3xl bg-slate-950 p-5 text-xs text-slate-100">{{ currentDataMr || '-' }}</pre>
                                </div>

                                <div class="rounded-3xl border border-slate-200 p-5">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-500">Data Sebelum Enkripsi</p>
                                        <button
                                            v-if="currentResult.raw_bundle"
                                            type="button"
                                            class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                            @click="copyText('Raw Bundle', currentResult.raw_bundle)"
                                        >
                                            Copy Raw Bundle
                                        </button>
                                    </div>
                                    <pre class="mt-4 max-h-[320px] overflow-auto whitespace-pre-wrap break-all rounded-3xl bg-slate-950 p-5 text-xs text-slate-100">{{ pretty(currentResult.raw_bundle ?? {}) }}</pre>
                                </div>

                                <div v-if="currentResult.bpjs_response" class="rounded-3xl border border-slate-200 p-5">
                                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-500">BPJS Response</p>
                                    <pre class="mt-4 max-h-[280px] overflow-auto whitespace-pre-wrap break-all rounded-3xl bg-slate-950 p-5 text-xs text-slate-100">{{ pretty(currentResult.bpjs_response) }}</pre>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="mt-5 rounded-3xl border border-dashed border-slate-200 px-5 py-10 text-center text-sm text-slate-500">
                        Belum ada preview. Jalankan preview atau insert live untuk melihat bundle, request payload, dan hasil dataMR.
                    </div>
                </article>

                <article class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)]">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-[var(--app-accent)]">Selected History</p>
                            <h3 class="mt-2 text-xl font-semibold text-slate-900">Detail Job Tersimpan</h3>
                        </div>
                        <button
                            v-if="selectedHistory?.payload?.request"
                            type="button"
                            class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                            @click="copyText('History Request', selectedHistory.payload.request)"
                        >
                            Copy Request
                        </button>
                    </div>

                    <div v-if="selectedHistory" class="mt-5 space-y-5">
                        <div class="grid gap-4 md:grid-cols-4">
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Status</p>
                                <p class="mt-2">
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="statusBadgeClass(selectedHistory.status)">
                                        {{ selectedHistory.status }}
                                    </span>
                                </p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Response Code</p>
                                <p class="mt-2 text-lg font-semibold text-slate-900">{{ selectedHistory.payload?.bpjs_response_code ?? '-' }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Encrypted Length</p>
                                <p class="mt-2 text-lg font-semibold text-slate-900">{{ selectedHistory.payload?.encrypted_payload_length ?? '-' }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Sent At</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">{{ formatDate(selectedHistory.sent_at) }}</p>
                            </div>
                        </div>

                        <div class="grid gap-5 xl:grid-cols-[320px,minmax(0,1fr)]">
                            <div class="rounded-3xl border border-slate-200 p-5">
                                <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-500">Snapshot</p>
                                <div class="mt-4 space-y-2">
                                    <div
                                        v-for="[name, total] in selectedHistorySnapshotEntries"
                                        :key="name"
                                        class="flex items-center justify-between rounded-2xl bg-slate-50 px-3 py-2 text-sm"
                                    >
                                        <span class="font-medium text-slate-700">{{ name }}</span>
                                        <span class="font-semibold text-slate-900">{{ total }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-5">
                                <div class="rounded-3xl border border-slate-200 p-5">
                                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-500">Stored Request</p>
                                    <pre class="mt-4 max-h-[280px] overflow-auto whitespace-pre-wrap break-all rounded-3xl bg-slate-950 p-5 text-xs text-slate-100">{{ pretty(selectedHistory.payload?.request) }}</pre>
                                </div>

                                <div class="rounded-3xl border border-slate-200 p-5">
                                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-500">Stored Raw Bundle</p>
                                    <pre class="mt-4 max-h-[280px] overflow-auto whitespace-pre-wrap break-all rounded-3xl bg-slate-950 p-5 text-xs text-slate-100">{{ pretty(selectedHistory.payload?.raw_bundle) }}</pre>
                                </div>

                                <div class="rounded-3xl border border-slate-200 p-5">
                                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-500">Stored Response</p>
                                    <pre class="mt-4 max-h-[280px] overflow-auto whitespace-pre-wrap break-all rounded-3xl bg-slate-950 p-5 text-xs text-slate-100">{{ pretty(selectedHistory.payload?.response) }}</pre>
                                </div>

                                <div v-if="selectedHistory.last_error" class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
                                    {{ selectedHistory.last_error }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="mt-5 rounded-3xl border border-dashed border-slate-200 px-5 py-10 text-center text-sm text-slate-500">
                        Belum ada history yang bisa ditampilkan.
                    </div>
                </article>
            </section>
        </div>
    </AdminLayout>
</template>
