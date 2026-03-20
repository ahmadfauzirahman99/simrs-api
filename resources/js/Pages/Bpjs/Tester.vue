<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, reactive, ref, watch } from 'vue';
import AdminLayout from '../../Layouts/AdminLayout.vue';

const props = defineProps({
    endpointGroups: {
        type: Array,
        default: () => [],
    },
    systemLabel: {
        type: String,
        default: 'BPJS',
    },
});

const page = usePage();

const flattened = props.endpointGroups.flatMap((group) =>
    group.items.map((item) => ({
        ...item,
        groupLabel: group.label,
    })),
);

const activeId = ref(flattened[0]?.id ?? null);
const executionMode = ref('live');
const mockScenario = ref('success');
const search = ref('');
const currentPage = ref(1);
const perPage = 7;
const loading = ref(false);
const result = ref(null);
const error = ref(null);
const copiedState = ref('');

const queryState = reactive(
    Object.fromEntries(
        flattened
            .filter((item) => item.mode === 'query')
            .map((item) => [
                item.id,
                Object.fromEntries((item.fields ?? []).map((field) => [field.key, field.value ?? ''])),
            ]),
    ),
);

const formState = reactive(
    Object.fromEntries(
        flattened
            .filter((item) => item.mode === 'form')
            .map((item) => [
                item.id,
                Object.fromEntries((item.formFields ?? []).map((field) => [field.path, field.value ?? ''])),
            ]),
    ),
);

const jsonState = reactive(
    Object.fromEntries(
        flattened
            .filter((item) => item.mode === 'json')
            .map((item) => [item.id, item.jsonTemplate ?? '{}']),
    ),
);

const activeEndpoint = computed(() => flattened.find((item) => item.id === activeId.value) ?? null);
const currentCorrelationId = computed(() => result.value?.headers?.['x-correlation-id'] ?? page.props?.correlationId ?? null);
const currentOrigin = computed(() => {
    if (typeof window === 'undefined') {
        return '';
    }

    return window.location.origin;
});
const filteredGroups = computed(() => {
    const keyword = search.value.trim().toLowerCase();

    if (!keyword) {
        return props.endpointGroups;
    }

    return props.endpointGroups
        .map((group) => ({
            ...group,
            items: group.items.filter((item) => {
                const haystack = [
                    item.title,
                    item.path,
                    item.method,
                    item.description,
                ]
                    .join(' ')
                    .toLowerCase();

                return haystack.includes(keyword);
            }),
        }))
        .filter((group) => group.items.length > 0);
});

const filteredItems = computed(() =>
    filteredGroups.value.flatMap((group) =>
        group.items.map((item) => ({
            ...item,
            groupLabel: group.label,
        })),
    ),
);

const totalPages = computed(() => Math.max(1, Math.ceil(filteredItems.value.length / perPage)));

const paginatedGroups = computed(() => {
    const start = (currentPage.value - 1) * perPage;
    const items = filteredItems.value.slice(start, start + perPage);
    const groups = new Map();

    items.forEach((item) => {
        if (!groups.has(item.groupLabel)) {
            groups.set(item.groupLabel, []);
        }

        groups.get(item.groupLabel).push(item);
    });

    return Array.from(groups.entries()).map(([label, items]) => ({
        id: label.toLowerCase().replace(/\s+/g, '-'),
        label,
        items,
    }));
});

watch(search, () => {
    currentPage.value = 1;
});

watch(totalPages, (value) => {
    if (currentPage.value > value) {
        currentPage.value = value;
    }
});

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

const toDisplayValue = (value) => {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    if (typeof value === 'object') {
        return JSON.stringify(value);
    }

    return String(value);
};

const responseSections = computed(() => {
    const payload = result.value?.body?.response;

    if (!payload || typeof payload !== 'object' || Array.isArray(payload)) {
        return [];
    }

    return Object.entries(payload)
        .filter(([, value]) => Array.isArray(value) && value.length > 0 && typeof value[0] === 'object' && value[0] !== null)
        .map(([key, rows]) => {
            const columns = Array.from(
                rows.reduce((set, row) => {
                    Object.keys(row).forEach((column) => set.add(column));
                    return set;
                }, new Set()),
            );

            return { key, columns, rows };
        });
});

const joinPath = (segments) =>
    segments.reduce((path, segment) => {
        if (segment.startsWith('[')) {
            return `${path}${segment}`;
        }

        return path ? `${path}.${segment}` : segment;
    }, '');

const codingLabel = (coding) => {
    if (!coding || typeof coding !== 'object') {
        return null;
    }

    return coding.display ?? coding.code ?? null;
};

const humanName = (name) => {
    if (!name) {
        return null;
    }

    if (typeof name === 'string') {
        return name;
    }

    if (Array.isArray(name)) {
        return humanName(name[0]);
    }

    if (Array.isArray(name.text) && name.text[0]) {
        return name.text[0];
    }

    if (name.text) {
        return name.text;
    }

    const parts = [...(name.prefix ?? []), ...(name.given ?? []), name.family].filter(Boolean);

    return parts.length ? parts.join(' ') : null;
};

const referenceLabel = (reference) => {
    if (!reference) {
        return null;
    }

    if (typeof reference === 'string') {
        return reference;
    }

    return reference.display ?? reference.reference ?? null;
};

const observationValue = (resource) => {
    if (resource.valueQuantity) {
        const value = [resource.valueQuantity.value, resource.valueQuantity.unit].filter(Boolean).join(' ');
        return value || null;
    }

    if (resource.valueString) {
        return resource.valueString;
    }

    if (resource.valueBoolean !== undefined) {
        return String(resource.valueBoolean);
    }

    if (resource.valueCodeableConcept) {
        return resource.valueCodeableConcept.text
            ?? codingLabel(resource.valueCodeableConcept.coding?.[0])
            ?? null;
    }

    return null;
};

const summarizeFhirResource = (resource) => {
    const summary = [
        { label: 'Resource Type', value: resource.resourceType },
        { label: 'ID', value: resource.id ?? null },
    ];

    switch (resource.resourceType) {
        case 'Encounter':
            summary.push(
                { label: 'Status', value: resource.status ?? null },
                { label: 'Class', value: codingLabel(resource.class) ?? resource.class?.code ?? null },
                { label: 'Subject', value: referenceLabel(resource.subject) },
                { label: 'Service Provider', value: referenceLabel(resource.serviceProvider) },
                {
                    label: 'Period',
                    value: [resource.period?.start, resource.period?.end].filter(Boolean).join(' -> ') || null,
                },
            );
            break;
        case 'Patient':
            summary.push(
                { label: 'Name', value: humanName(resource.name) },
                { label: 'Gender', value: resource.gender ?? null },
                { label: 'Birth Date', value: resource.birthDate ?? null },
                { label: 'Identifier', value: resource.identifier?.map((item) => item.value).filter(Boolean).join(', ') || null },
            );
            break;
        case 'Observation':
            summary.push(
                { label: 'Status', value: resource.status ?? null },
                { label: 'Code', value: resource.code?.text ?? codingLabel(resource.code?.coding?.[0]) ?? null },
                { label: 'Subject', value: referenceLabel(resource.subject) },
                { label: 'Effective', value: resource.effectiveDateTime ?? null },
                { label: 'Value', value: observationValue(resource) },
            );
            break;
        default:
            summary.push(
                { label: 'Status', value: resource.status ?? resource.intent ?? null },
                { label: 'Code', value: resource.code?.text ?? codingLabel(resource.code?.coding?.[0]) ?? null },
                { label: 'Subject', value: referenceLabel(resource.subject) },
            );
            break;
    }

    return summary.filter((item) => item.value !== null && item.value !== undefined && item.value !== '');
};

const fhirResources = computed(() => {
    const resources = [];
    const visit = (value, segments = []) => {
        if (!value || typeof value !== 'object') {
            return;
        }

        if (Array.isArray(value)) {
            value.forEach((item, index) => visit(item, [...segments, `[${index}]`]));
            return;
        }

        if (typeof value.resourceType === 'string') {
            resources.push({
                path: joinPath(segments),
                resource: value,
                summary: summarizeFhirResource(value),
            });
        }

        Object.entries(value).forEach(([key, child]) => {
            visit(child, [...segments, key]);
        });
    };

    visit(result.value?.body ?? null, ['body']);

    return resources;
});

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

const shouldShowField = (endpointId, field) => {
    if (!field.showWhen) {
        return true;
    }

    return (formState[endpointId]?.[field.showWhen.path] ?? '') === field.showWhen.equals;
};

const setNestedValue = (target, path, value) => {
    const segments = path.split('.');
    let cursor = target;

    segments.forEach((segment, index) => {
        if (index === segments.length - 1) {
            cursor[segment] = value;
            return;
        }

        if (typeof cursor[segment] !== 'object' || cursor[segment] === null) {
            cursor[segment] = {};
        }

        cursor = cursor[segment];
    });
};

const buildPreview = () => {
    const endpoint = activeEndpoint.value;

    if (!endpoint) {
        return null;
    }

    if (endpoint.mode === 'query') {
        return {
            params: { ...(queryState[endpoint.id] ?? {}) },
        };
    }

    if (endpoint.mode === 'form') {
        const payload = {};

        (endpoint.formFields ?? []).forEach((field) => {
            const path = field.path;
            const value = formState[endpoint.id]?.[path] ?? '';

            if (path.startsWith('ui.')) {
                return;
            }

            if (!shouldShowField(endpoint.id, field)) {
                return;
            }

            setNestedValue(payload, path, value);
        });

        return payload;
    }

    try {
        return JSON.parse(jsonState[endpoint.id] ?? '{}');
    } catch {
        return null;
    }
};

const requestQueryString = (value) => {
    if (!value || typeof value !== 'object' || Array.isArray(value)) {
        return '';
    }

    const params = new URLSearchParams();

    Object.entries(value).forEach(([key, paramValue]) => {
        if (paramValue === null || paramValue === undefined || paramValue === '') {
            return;
        }

        params.append(key, String(paramValue));
    });

    const query = params.toString();

    return query ? `?${query}` : '';
};

const requestTargetUrl = computed(() => {
    const endpoint = activeEndpoint.value;

    if (!endpoint) {
        return '-';
    }

    const base = `${currentOrigin.value}${endpoint.path}`;

    if (endpoint.method === 'GET') {
        const preview = buildPreview();
        return `${base}${requestQueryString(preview?.params ?? preview)}`;
    }

    return base;
});

const vendorTargetUrl = computed(() => {
    if (result.value?.type === 'mock') {
        return 'Mock Preview tidak memanggil vendor BPJS';
    }

    return result.value?.headers?.['x-target-url'] ?? '-';
});

const vendorTargetMethod = computed(() => {
    if (result.value?.type === 'mock') {
        return '-';
    }

    return result.value?.headers?.['x-target-method'] ?? '-';
});

const vendorDebugHeaders = computed(() => {
    if (result.value?.type === 'mock') {
        return {
            'X-cons-id': '-',
            'X-timestamp': '-',
            user_key: '-',
            'X-signature': '-',
        };
    }

    return {
        'X-cons-id': result.value?.headers?.['x-target-cons-id'] ?? '-',
        'X-timestamp': result.value?.headers?.['x-target-timestamp'] ?? '-',
        user_key: result.value?.headers?.['x-target-user-key-masked'] ?? '-',
        'X-signature': result.value?.headers?.['x-target-signature-present'] === 'yes' ? 'present' : '-',
    };
});

const vendorFinalPayload = computed(() => {
    if (result.value?.type === 'mock') {
        return {
            info: 'Mock Preview tidak mengirim payload ke vendor BPJS',
        };
    }

    const encoded = result.value?.headers?.['x-target-payload'];

    if (!encoded) {
        return activeEndpoint.value?.method === 'GET'
            ? { info: 'GET request tidak memiliki body payload outbound.' }
            : { info: 'Payload outbound tidak tersedia.' };
    }

    try {
        const decoded = atob(encoded);

        if (!decoded) {
            return activeEndpoint.value?.method === 'GET'
                ? { info: 'GET request tidak memiliki body payload outbound.' }
                : { info: 'Payload outbound kosong.' };
        }

        try {
            return JSON.parse(decoded);
        } catch {
            return decoded;
        }
    } catch {
        return { info: 'Gagal membaca payload outbound vendor.' };
    }
});

const vendorFinalQuery = computed(() => {
    if (result.value?.type === 'mock') {
        return {
            info: 'Mock Preview tidak mengirim query params ke vendor BPJS',
        };
    }

    const encoded = result.value?.headers?.['x-target-query'];

    if (!encoded) {
        return { info: 'Query params outbound tidak tersedia.' };
    }

    try {
        const decoded = atob(encoded);

        if (!decoded) {
            return activeEndpoint.value?.method === 'GET'
                ? { info: 'GET request ini tidak memiliki query params outbound.' }
                : { info: 'Request non-GET tidak menggunakan query params outbound.' };
        }

        try {
            return JSON.parse(decoded);
        } catch {
            return decoded;
        }
    } catch {
        return { info: 'Gagal membaca query params outbound vendor.' };
    }
});

const runEndpoint = async () => {
    const endpoint = activeEndpoint.value;

    if (!endpoint) {
        return;
    }

    loading.value = true;
    error.value = null;

    if (executionMode.value === 'mock') {
        const selectedMock = endpoint.mockResponses?.[mockScenario.value] ?? endpoint.mockResponses?.success ?? {};

        result.value = {
            type: 'mock',
            status: Number(selectedMock?.metaData?.code ?? 200),
            headers: {},
            request: buildPreview(),
            body: selectedMock,
        };
        loading.value = false;
        return;
    }

    try {
        let response;

        if (endpoint.mode === 'query') {
            response = await axios.request({
                method: endpoint.method,
                url: endpoint.path,
                params: queryState[endpoint.id] ?? {},
            });
        } else if (endpoint.mode === 'form') {
            response = await axios.request({
                method: endpoint.method,
                url: endpoint.path,
                data: buildPreview(),
                headers: {
                    'Content-Type': 'application/json',
                },
            });
        } else {
            const parsed = JSON.parse(jsonState[endpoint.id] ?? '{}');

            response = await axios.request({
                method: endpoint.method,
                url: endpoint.path,
                data: parsed,
                headers: {
                    'Content-Type': 'application/json',
                },
            });
        }

        result.value = {
            type: 'live',
            status: response.status,
            headers: response.headers,
            request: buildPreview(),
            body: response.data,
        };
    } catch (err) {
        const response = err.response;

        error.value = err.message;
        result.value = {
            type: 'live',
            status: response?.status ?? 500,
            headers: response?.headers ?? {},
            request: buildPreview(),
            body: response?.data ?? {
                metaData: {
                    code: '500',
                    message: err.message,
                },
                response: {},
            },
        };
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <Head :title="`${systemLabel} Tester`" />

    <AdminLayout>
        <div class="grid gap-6 xl:grid-cols-[360px_minmax(0,1fr)]">
            <aside class="rounded-[28px] border border-white/60 bg-white/85 p-5 shadow-[0_20px_60px_rgba(15,23,42,0.08)] backdrop-blur xl:sticky xl:top-6 xl:flex xl:h-[calc(100vh-3rem)] xl:flex-col xl:overflow-hidden">
                <div class="mb-4 rounded-2xl bg-slate-900 p-4 text-white">
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Execution Mode</p>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            class="rounded-xl px-3 py-2 text-sm font-medium transition"
                            :class="executionMode === 'live' ? 'bg-[var(--app-accent)] text-white' : 'bg-white/10 text-slate-300 hover:bg-white/15'"
                            @click="executionMode = 'live'"
                        >
                            Live API
                        </button>
                        <button
                            type="button"
                            class="rounded-xl px-3 py-2 text-sm font-medium transition"
                            :class="executionMode === 'mock' ? 'bg-[var(--app-sand-strong)] text-slate-900' : 'bg-white/10 text-slate-300 hover:bg-white/15'"
                            @click="executionMode = 'mock'"
                        >
                            Mock Preview
                        </button>
                    </div>
                    <div class="mt-3">
                        <label class="mb-2 block text-xs uppercase tracking-[0.25em] text-slate-400">Mock Scenario</label>
                        <select
                            v-model="mockScenario"
                            class="w-full rounded-xl border border-white/10 bg-white/10 px-3 py-2 text-sm text-white outline-none transition"
                        >
                            <option value="success" class="text-slate-900">Success</option>
                            <option value="failed" class="text-slate-900">Failed</option>
                        </select>
                    </div>
                    <p class="mt-3 text-xs leading-5 text-slate-400">
                        Live API akan memanggil endpoint Laravel yang sudah ada. Mock Preview hanya menampilkan simulasi response.
                    </p>
                </div>

                <div class="mb-4">
                    <label class="mb-2 block px-1 text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Cari API</label>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Cari nama endpoint, method, path..."
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-[var(--app-accent)] focus:ring-4 focus:ring-emerald-100"
                    >
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto pr-1">
                    <div class="space-y-4 pb-4">
                    <section v-for="group in paginatedGroups" :key="group.id">
                        <p class="mb-2 px-2 text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">{{ group.label }}</p>
                        <div class="space-y-2">
                            <button
                                v-for="item in group.items"
                                :key="item.id"
                                type="button"
                                class="w-full rounded-2xl border px-4 py-3 text-left transition"
                                :class="activeId === item.id
                                    ? 'border-[var(--app-accent)] bg-emerald-50 shadow-[0_14px_28px_rgba(16,185,129,0.12)]'
                                    : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50'"
                                @click="activeId = item.id"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">{{ item.title }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ item.method }} {{ item.path }}</p>
                                    </div>
                                    <span class="rounded-full bg-slate-900 px-2.5 py-1 text-[11px] font-semibold text-white">
                                        {{ item.method }}
                                    </span>
                                </div>
                            </button>
                        </div>
                    </section>

                    <div v-if="paginatedGroups.length === 0" class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                        Endpoint tidak ditemukan untuk kata kunci ini.
                    </div>
                    </div>
                </div>

                <div v-if="filteredItems.length > perPage" class="mt-4 flex shrink-0 items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">
                        Page {{ currentPage }} / {{ totalPages }}
                    </p>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="currentPage === 1"
                            @click="currentPage -= 1"
                        >
                            Prev
                        </button>
                        <button
                            type="button"
                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="currentPage === totalPages"
                            @click="currentPage += 1"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </aside>

            <section v-if="activeEndpoint" class="space-y-6">
                <div class="rounded-[28px] border border-white/60 bg-white/85 p-7 shadow-[0_20px_60px_rgba(15,23,42,0.08)] backdrop-blur">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-[var(--app-accent)]">{{ activeEndpoint.groupLabel }}</p>
                            <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ activeEndpoint.title }}</h2>
                            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">{{ activeEndpoint.description }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-900 px-4 py-3 text-white">
                            <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Endpoint</p>
                            <p class="mt-1 text-sm font-semibold">{{ activeEndpoint.method }} {{ activeEndpoint.path }}</p>
                        </div>
                    </div>

                    <div class="mt-7 grid gap-6 2xl:grid-cols-[minmax(0,1.15fr)_minmax(420px,0.85fr)]">
                        <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-6">
                            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Request Builder</p>

                            <div v-if="activeEndpoint.mode === 'query'" class="mt-4 grid gap-4">
                                <div v-for="field in activeEndpoint.fields" :key="field.key">
                                    <label class="mb-2 block text-sm font-medium text-slate-700">{{ field.label }}</label>

                                    <select
                                        v-if="field.type === 'select'"
                                        v-model="queryState[activeEndpoint.id][field.key]"
                                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-[var(--app-accent)] focus:ring-4 focus:ring-emerald-100"
                                    >
                                        <option v-for="option in field.options" :key="option.value" :value="option.value">
                                            {{ option.label }}
                                        </option>
                                    </select>

                                    <input
                                        v-else
                                        v-model="queryState[activeEndpoint.id][field.key]"
                                        :type="field.type"
                                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-[var(--app-accent)] focus:ring-4 focus:ring-emerald-100"
                                    >
                                </div>
                            </div>

                            <div v-else-if="activeEndpoint.mode === 'form'" class="mt-4 grid gap-4 xl:grid-cols-2">
                                <div
                                    v-for="field in activeEndpoint.formFields"
                                    :key="field.path"
                                    v-show="shouldShowField(activeEndpoint.id, field)"
                                    :class="field.type === 'textarea' ? 'md:col-span-2' : ''"
                                >
                                    <label class="mb-2 block text-sm font-medium text-slate-700">{{ field.label }}</label>

                                    <select
                                        v-if="field.type === 'select'"
                                        v-model="formState[activeEndpoint.id][field.path]"
                                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-[var(--app-accent)] focus:ring-4 focus:ring-emerald-100"
                                    >
                                        <option v-for="option in field.options" :key="option.value" :value="option.value">
                                            {{ option.label }}
                                        </option>
                                    </select>

                                    <textarea
                                        v-else-if="field.type === 'textarea'"
                                        v-model="formState[activeEndpoint.id][field.path]"
                                        rows="4"
                                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-[var(--app-accent)] focus:ring-4 focus:ring-emerald-100"
                                    />

                                    <input
                                        v-else
                                        v-model="formState[activeEndpoint.id][field.path]"
                                        :type="field.type"
                                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-[var(--app-accent)] focus:ring-4 focus:ring-emerald-100"
                                    >
                                </div>
                            </div>

                            <div v-else class="mt-4">
                                <label class="mb-2 block text-sm font-medium text-slate-700">JSON Payload</label>
                                <textarea
                                    v-model="jsonState[activeEndpoint.id]"
                                    rows="22"
                                    class="w-full rounded-2xl border border-slate-200 bg-slate-950 px-4 py-3 font-mono text-sm text-emerald-100 outline-none transition focus:border-[var(--app-accent)] focus:ring-4 focus:ring-emerald-100"
                                />
                            </div>

                            <div class="mt-5 flex flex-wrap items-center gap-3">
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-2xl bg-[var(--app-accent)] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[var(--app-accent-strong)] disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="loading"
                                    @click="runEndpoint"
                                >
                                    {{ loading ? 'Memproses...' : executionMode === 'live' ? 'Jalankan Live API' : 'Tampilkan Mock Preview' }}
                                </button>

                                <span class="rounded-full bg-[var(--app-sand)] px-3 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-slate-800">
                                    {{ executionMode === 'live' ? 'Mode Live' : 'Mode Mock' }}
                                </span>
                                <button
                                    type="button"
                                    class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                                    @click="copyText('Payload', buildPreview())"
                                >
                                    Copy Payload
                                </button>
                            </div>
                        </div>

                        <div class="rounded-[24px] border border-slate-200 bg-white p-6">
                            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Request Preview</p>
                            <pre class="mt-4 overflow-x-auto rounded-2xl bg-slate-950 p-4 text-xs leading-6 text-emerald-100">{{ pretty(buildPreview()) }}</pre>

                            <div class="mt-4 grid gap-3 md:grid-cols-2">
                                <div class="rounded-2xl bg-slate-100 p-4">
                                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Method</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ activeEndpoint.method }}</p>
                                </div>
                                <div class="rounded-2xl bg-slate-100 p-4">
                                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Path</p>
                                    <p class="mt-2 break-all text-sm font-semibold text-slate-900">{{ activeEndpoint.path }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-[28px] border border-white/60 bg-white/85 p-7 shadow-[0_20px_60px_rgba(15,23,42,0.08)] backdrop-blur">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Execution Result</p>
                            <h3 class="mt-2 text-xl font-semibold text-slate-900">Response Console</h3>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <div class="rounded-2xl bg-slate-900 px-4 py-3 text-white">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Status</p>
                                <p class="mt-1 text-sm font-semibold">{{ result?.status ?? '-' }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-100 px-4 py-3">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Correlation ID</p>
                                <p class="mt-1 max-w-[260px] truncate text-sm font-semibold text-slate-900">
                                    {{ currentCorrelationId ?? '-' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                            @click="copyText('Response', result?.body ?? {})"
                        >
                            Copy Response
                        </button>

                        <span v-if="copiedState" class="text-sm font-medium text-[var(--app-accent)]">
                            {{ copiedState }}
                        </span>
                    </div>

                    <p v-if="error" class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        {{ error }}
                    </p>

                    <div class="mt-6 grid gap-6 xl:grid-cols-2">
                        <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-5">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">URL Hit</p>
                                    <p class="mt-2 break-all text-sm font-semibold text-slate-900">{{ requestTargetUrl }}</p>
                                </div>
                                <button
                                    type="button"
                                    class="rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                                    @click="copyText('URL Hit', requestTargetUrl)"
                                >
                                    Copy URL
                                </button>
                            </div>
                        </div>

                        <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-5">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">URL BPJS Hit</p>
                                    <p class="mt-2 break-all text-sm font-semibold text-slate-900">{{ vendorTargetUrl }}</p>
                                </div>
                                <button
                                    type="button"
                                    class="rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                                    :disabled="vendorTargetUrl === '-' || vendorTargetUrl === 'Mock Preview tidak memanggil vendor BPJS'"
                                    @click="copyText('URL BPJS Hit', vendorTargetUrl)"
                                >
                                    Copy Vendor URL
                                </button>
                            </div>

                            <div class="mt-4 grid gap-3 md:grid-cols-2">
                                <div class="rounded-2xl bg-white p-4">
                                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Mode</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900">
                                        {{ result?.type === 'mock' ? 'Mock Preview' : 'Live API' }}
                                    </p>
                                </div>
                                <div class="rounded-2xl bg-white p-4">
                                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Vendor Method</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ vendorTargetMethod }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-6 2xl:grid-cols-2">
                        <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-5">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Request Sent</p>
                                    <p class="mt-2 text-sm text-slate-600">Payload atau query yang dikirim dari dashboard tester.</p>
                                </div>
                                <button
                                    type="button"
                                    class="rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                                    @click="copyText('Request', result?.request ?? buildPreview() ?? {})"
                                >
                                    Copy Request
                                </button>
                            </div>

                            <pre class="mt-4 overflow-x-auto rounded-2xl bg-slate-950 p-4 text-xs leading-6 text-cyan-100">{{ pretty(result?.request ?? buildPreview() ?? {}) }}</pre>
                        </div>

                        <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-5">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">BPJS Request Headers</p>
                                    <p class="mt-2 text-sm text-slate-600">Header vendor yang aman untuk debugging.</p>
                                </div>
                                <button
                                    type="button"
                                    class="rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                                    @click="copyText('BPJS Request Headers', vendorDebugHeaders)"
                                >
                                    Copy Headers
                                </button>
                            </div>

                            <pre class="mt-4 overflow-x-auto rounded-2xl bg-slate-950 p-4 text-xs leading-6 text-cyan-100">{{ pretty(vendorDebugHeaders) }}</pre>
                        </div>
                    </div>

                    <div class="mt-6 rounded-[24px] border border-slate-200 bg-slate-50 p-5">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">BPJS Final Outbound Payload</p>
                                <p class="mt-2 text-sm text-slate-600">Payload final yang benar-benar diteruskan backend ke vendor BPJS.</p>
                            </div>
                            <button
                                type="button"
                                class="rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                                @click="copyText('BPJS Final Outbound Payload', vendorFinalPayload)"
                            >
                                Copy Final Payload
                            </button>
                        </div>

                        <pre class="mt-4 overflow-x-auto rounded-2xl bg-slate-950 p-4 text-xs leading-6 text-cyan-100">{{ pretty(vendorFinalPayload) }}</pre>
                    </div>

                    <div class="mt-6 rounded-[24px] border border-slate-200 bg-slate-50 p-5">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">BPJS Final Query Params</p>
                                <p class="mt-2 text-sm text-slate-600">Query params final yang diteruskan backend ke vendor BPJS untuk request GET.</p>
                            </div>
                            <button
                                type="button"
                                class="rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                                @click="copyText('BPJS Final Query Params', vendorFinalQuery)"
                            >
                                Copy Final Query
                            </button>
                        </div>

                        <pre class="mt-4 overflow-x-auto rounded-2xl bg-slate-950 p-4 text-xs leading-6 text-cyan-100">{{ pretty(vendorFinalQuery) }}</pre>
                    </div>

                    <div v-if="responseSections.length" class="mt-6 space-y-4">
                        <div
                            v-for="section in responseSections"
                            :key="section.key"
                            class="rounded-[24px] border border-slate-200 bg-slate-50 p-5"
                        >
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Structured Response</p>
                                    <h4 class="mt-1 text-lg font-semibold text-slate-900">{{ section.key }}</h4>
                                </div>
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-slate-600">
                                    {{ section.rows.length }} row
                                </span>
                            </div>

                            <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-100">
                                        <tr>
                                            <th
                                                v-for="column in section.columns"
                                                :key="column"
                                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500"
                                            >
                                                {{ column }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="(row, index) in section.rows" :key="`${section.key}-${index}`">
                                            <td
                                                v-for="column in section.columns"
                                                :key="column"
                                                class="px-4 py-3 align-top text-slate-700"
                                            >
                                                {{ toDisplayValue(row[column]) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div v-if="fhirResources.length" class="mt-6 space-y-4">
                        <div
                            v-for="(entry, index) in fhirResources"
                            :key="`${entry.path}-${index}`"
                            class="rounded-[24px] border border-cyan-200 bg-cyan-50/70 p-5"
                        >
                            <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-cyan-700">FHIR Resource</p>
                                    <h4 class="mt-1 text-lg font-semibold text-slate-900">
                                        {{ entry.resource.resourceType }}{{ entry.resource.id ? ` · ${entry.resource.id}` : '' }}
                                    </h4>
                                    <p class="mt-1 text-xs text-slate-500">{{ entry.path }}</p>
                                </div>

                                <button
                                    type="button"
                                    class="rounded-2xl border border-cyan-200 bg-white px-3 py-2 text-sm font-semibold text-cyan-800 transition hover:bg-cyan-100"
                                    @click="copyText(`${entry.resource.resourceType} Resource`, entry.resource)"
                                >
                                    Copy Resource
                                </button>
                            </div>

                            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                <div
                                    v-for="item in entry.summary"
                                    :key="`${entry.path}-${item.label}`"
                                    class="rounded-2xl border border-white/80 bg-white px-4 py-3"
                                >
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">{{ item.label }}</p>
                                    <p class="mt-2 break-words text-sm font-medium text-slate-900">{{ item.value }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-6 2xl:grid-cols-2">
                        <div>
                            <p class="mb-3 text-sm font-semibold text-slate-700">Response Headers</p>
                            <pre class="overflow-x-auto rounded-2xl bg-slate-950 p-4 text-xs leading-6 text-cyan-100">{{ pretty(result?.headers ?? {}) }}</pre>
                        </div>
                        <div>
                            <p class="mb-3 text-sm font-semibold text-slate-700">Response Body</p>
                            <pre class="overflow-x-auto rounded-2xl bg-slate-950 p-4 text-xs leading-6 text-emerald-100">{{ pretty(result?.body ?? { info: 'Belum ada response. Jalankan request terlebih dahulu.' }) }}</pre>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
