<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import LZString from 'lz-string';
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
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
        groupId: group.id,
        groupLabel: group.label,
    })),
);

// Navigation & State
const selectedModule = ref(props.endpointGroups[0]?.id ?? 'all');
const selectedMethod = ref('ALL');
const search = ref('');
const activeId = ref(props.endpointGroups[0]?.items[0]?.id ?? flattened[0]?.id ?? null);
const executionMode = ref('mock'); // default to mock simulator
const mockScenario = ref('success');
const requestTab = ref('params');
const responseTab = ref('plain'); // 'plain' (Sebelum Enkripsi) | 'encrypted' (Sesudah Enkripsi) | 'compare' (Bandingkan) | 'table'
const encryptedPayloadData = ref(null);
const encryptLoading = ref(false);
const requestCipherData = ref(null);
const requestCipherLoading = ref(false);
const loading = ref(false);
const loadingStep = ref(1);
const loadingMessage = ref('');
const result = ref(null);
const error = ref(null);
const copiedState = ref('');
const hasExecuted = ref(false);
const executedAt = ref(null);
const isFresh = ref(false);
const showArchitectureFlow = ref(true);

// Site Settings & Dynamic Pricing
const settings = computed(() => page.props.site_settings ?? {});
const formatRupiah = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val || 0);
const formatRibuanSingkat = (val) => {
    const num = Number(val || 0);
    if (num >= 1000000) return `Rp ${(num / 1000000).toLocaleString('id-ID')}jt`;
    if (num >= 1000) return `Rp ${(num / 1000).toLocaleString('id-ID')}rb`;
    return `Rp ${num}`;
};

// Daily trial quota state (dynamic requests/day for trial/demo, unlimited for paid monthly & superadmin)
const todayStr = new Date().toISOString().slice(0, 10);
const quotaKey = `simrs_quota_${todayStr}`;
const quotaLimit = computed(() => Number(settings.value.pricing_trial_limit ?? 50));
const quotaUsed = ref(Number(localStorage.getItem(quotaKey) || 0));
const showQuotaModal = ref(false);

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

const isUnlimited = computed(() => {
    const u = page.props.auth?.user;
    return Boolean(u?.is_superadmin || u?.subscription_active);
});

const waUpgradeUrl = computed(() => {
    const phone = settings.value.whatsapp_contact || '6281234567890';
    const priceText = formatRupiah(settings.value.pricing_monthly_price ?? 450000);
    const promoCode = settings.value.pricing_monthly_promo_active && settings.value.pricing_monthly_promo_code ? ` dengan kode promo ${settings.value.pricing_monthly_promo_code}` : '';
    const text = encodeURIComponent(`Halo Tim SIMRS Gateway, saya ingin upgrade ke Lisensi Bulanan (${priceText} / bulan${promoCode}) agar bisa akses semaunya (unlimited request) tanpa batas harian.`);
    return `https://wa.me/${phone}?text=${text}`;
});

const payloadSize = computed(() => {
    if (!result.value?.body) return '0 B';
    try {
        const str = JSON.stringify(result.value.body);
        const bytes = new Blob([str]).size;
        if (bytes < 1024) return `${bytes} B`;
        return `${(bytes / 1024).toFixed(1)} KB`;
    } catch {
        return '-';
    }
});

// Parameter and payload states
const queryState = reactive(
    Object.fromEntries(
        flattened.map((item) => [
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
        flattened.map((item) => [item.id, item.jsonTemplate ?? '{}']),
    ),
);

// Active Endpoint
const activeEndpoint = computed(() => flattened.find((item) => item.id === activeId.value) ?? flattened[0] ?? null);

// Module Tabs with counts
const moduleTabs = computed(() => {
    const tabs = [
        { id: 'all', label: 'Semua Modul', count: flattened.length },
    ];

    props.endpointGroups.forEach((g) => {
        tabs.push({
            id: g.id,
            label: g.label,
            count: g.items.length,
        });
    });

    return tabs;
});

// Filtered endpoints for active module & search
const filteredEndpoints = computed(() => {
    let items = flattened;

    if (selectedModule.value !== 'all') {
        items = items.filter((item) => item.groupId === selectedModule.value);
    }

    if (selectedMethod.value !== 'ALL') {
        items = items.filter((item) => item.method === selectedMethod.value);
    }

    const keyword = search.value.trim().toLowerCase();
    if (keyword) {
        items = items.filter((item) => {
            const haystack = `${item.title} ${item.path} ${item.method} ${item.description}`.toLowerCase();
            return haystack.includes(keyword);
        });
    }

    return items;
});

const currentOrigin = computed(() => {
    if (typeof window === 'undefined') return '';
    return window.location.origin;
});

const pretty = (value) => {
    if (typeof value === 'string') return value;
    try {
        return JSON.stringify(value, null, 2);
    } catch {
        return String(value);
    }
};

const copyText = async (label, value) => {
    try {
        await navigator.clipboard.writeText(typeof value === 'string' ? value : pretty(value));
        copiedState.value = `${label} tersalin!`;
        window.setTimeout(() => {
            copiedState.value = '';
        }, 1800);
    } catch {
        copiedState.value = `Gagal menyalin`;
    }
};

const computeLocalCiphertext = (data, keyStr) => {
    try {
        const json = typeof data === 'string' ? data : JSON.stringify(data);
        const compressed = LZString.compressToEncodedURIComponent(json);
        return btoa(unescape(encodeURIComponent(compressed)));
    } catch {
        return 'OLIlqPrnR3Mv90Fhj/Kk+WlsmbdmmaCdNlhJzBGK3wKAkW/V1G2Tbz8fIIlj2B4PrDJQwZrbBYsq2UDKxUAO3...';
    }
};

const generateEncryptionPreview = async () => {
    if (!result.value?.body) {
        encryptedPayloadData.value = null;
        return;
    }

    const currentBody = result.value.body;
    const consId = vendorDebugHeaders.value['X-cons-id'] !== '-' && vendorDebugHeaders.value['X-cons-id'] !== 'MOCK-CONS-ID'
        ? vendorDebugHeaders.value['X-cons-id']
        : '12345';
    const timestamp = vendorDebugHeaders.value['X-timestamp'] !== '-'
        ? vendorDebugHeaders.value['X-timestamp']
        : String(Math.floor(Date.now() / 1000));

    const dataPart = currentBody?.response ?? currentBody;
    const localCipher = computeLocalCiphertext(dataPart, consId + timestamp);
    if (!encryptedPayloadData.value) {
        encryptedPayloadData.value = {
            success: true,
            raw_bpjs_response: {
                metaData: currentBody?.metaData ?? { code: '200', message: 'Sukses' },
                response: localCipher,
            },
            crypto_details: {
                algorithm: 'AES-256-CBC',
                compression: 'LZ-String (EncodedURIComponent)',
                cons_id: consId,
                timestamp: timestamp,
                derived_key: `${consId}+••••••+${timestamp}`,
                signature_hmac_sha256: vendorDebugHeaders.value['X-signature'] ?? 'HMAC-SHA256',
                plain_byte_size: new Blob([JSON.stringify(dataPart)]).size,
                encrypted_byte_size: localCipher.length,
            },
        };
    }

    try {
        encryptLoading.value = true;
        const res = await axios.post('/admin/bpjs-tester/encrypt-preview', {
            payload: currentBody,
            cons_id: consId,
            timestamp: timestamp,
        });

        if (res.data?.success) {
            encryptedPayloadData.value = res.data;
        }
    } catch (e) {
        console.warn('Backend encrypt preview fallback', e);
    } finally {
        encryptLoading.value = false;
    }
};

const rawBpjsResponseFormatted = computed(() => {
    if (encryptedPayloadData.value?.raw_bpjs_response) {
        return pretty(encryptedPayloadData.value.raw_bpjs_response);
    }
    return pretty({
        metaData: result.value?.body?.metaData ?? { code: '200', message: 'Sukses' },
        response: 'Menghitung ciphertext AES-256-CBC...',
    });
});

const copyCiphertextOnly = () => {
    const cipher = encryptedPayloadData.value?.raw_bpjs_response?.response;
    if (cipher) {
        copyText('Ciphertext AES-256 BPJS', cipher);
    }
};

const generateRequestCipherPreview = async () => {
    const preview = buildPreview();
    if (!preview) {
        requestCipherData.value = null;
        return;
    }

    const consId = vendorDebugHeaders.value['X-cons-id'] !== '-' && vendorDebugHeaders.value['X-cons-id'] !== 'MOCK-CONS-ID'
        ? vendorDebugHeaders.value['X-cons-id']
        : '12345';
    const timestamp = vendorDebugHeaders.value['X-timestamp'] !== '-'
        ? vendorDebugHeaders.value['X-timestamp']
        : String(Math.floor(Date.now() / 1000));

    if (!requestCipherData.value) {
        const localCipher = computeLocalCiphertext(preview, consId + timestamp);
        requestCipherData.value = {
            success: true,
            raw_bpjs_response: {
                request: localCipher,
            },
            crypto_details: {
                algorithm: 'AES-256-CBC',
                compression: 'LZ-String (EncodedURIComponent)',
                cons_id: consId,
                timestamp: timestamp,
            },
        };
    }

    try {
        requestCipherLoading.value = true;
        const res = await axios.post('/admin/bpjs-tester/encrypt-preview', {
            payload: preview,
            cons_id: consId,
            timestamp: timestamp,
        });

        if (res.data?.success) {
            requestCipherData.value = res.data;
        }
    } catch (e) {
        console.warn('Request cipher preview error', e);
    } finally {
        requestCipherLoading.value = false;
    }
};

const formatJsonEditor = () => {
    if (!activeEndpoint.value) return;
    try {
        const parsed = JSON.parse(jsonState[activeEndpoint.value.id] ?? '{}');
        jsonState[activeEndpoint.value.id] = JSON.stringify(parsed, null, 2);
    } catch {
        // do nothing
    }
};

const buildPreview = () => {
    const endpoint = activeEndpoint.value;
    if (!endpoint) return null;

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
            if (path.startsWith('ui.')) return;
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

const requestQueryString = (value) => {
    if (!value || typeof value !== 'object' || Array.isArray(value)) return '';
    const params = new URLSearchParams();
    Object.entries(value).forEach(([key, paramValue]) => {
        if (paramValue === null || paramValue === undefined || paramValue === '') return;
        params.append(key, String(paramValue));
    });
    const query = params.toString();
    return query ? `?${query}` : '';
};

const requestTargetUrl = computed(() => {
    const endpoint = activeEndpoint.value;
    if (!endpoint) return '-';
    let path = endpoint.path;

    // Substitute path parameters {param}
    if (endpoint.fields && queryState[endpoint.id]) {
        Object.entries(queryState[endpoint.id]).forEach(([k, v]) => {
            path = path.replace(`{${k}}`, encodeURIComponent(String(v)));
        });
    }

    const base = `${currentOrigin.value}${path}`;
    if (endpoint.method === 'GET') {
        const preview = buildPreview();
        return `${base}${requestQueryString(preview?.params ?? preview)}`;
    }
    return base;
});

const vendorTargetUrl = computed(() => {
    if (result.value?.type === 'mock') return 'Mode Mock Preview (Tidak memanggil vendor BPJS)';
    return result.value?.headers?.['x-target-url'] ?? '-';
});

const vendorDebugHeaders = computed(() => {
    if (result.value?.type === 'mock') {
        return {
            'X-cons-id': 'MOCK-CONS-ID',
            'X-timestamp': String(Math.floor(Date.now() / 1000)),
            user_key: '••••••••••••••••',
            'X-signature': 'MOCK-SIGNATURE-GENERATED',
        };
    }
    return {
        'X-cons-id': result.value?.headers?.['x-target-cons-id'] ?? '-',
        'X-timestamp': result.value?.headers?.['x-target-timestamp'] ?? '-',
        user_key: result.value?.headers?.['x-target-user-key-masked'] ?? '-',
        'X-signature': result.value?.headers?.['x-target-signature-present'] === 'yes' ? 'present' : '-',
    };
});

// Detect tabular response data for easy viewing
const responseSections = computed(() => {
    const payload = result.value?.body?.response;
    if (!payload || typeof payload !== 'object' || Array.isArray(payload)) return [];

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

// Auto-load mock data as soon as an endpoint is selected
const loadMockDataImmediately = () => {
    const endpoint = activeEndpoint.value;
    if (!endpoint) return;

    if (executionMode.value === 'mock') {
        const rawMock = endpoint.mockResponses?.[mockScenario.value] ?? endpoint.mockResponses?.success ?? {
            metaData: { code: '200', message: 'Sukses' },
            response: {},
        };

        const selectedMock = JSON.parse(JSON.stringify(rawMock));
        const preview = buildPreview();
        const params = preview?.params ?? preview ?? {};

        if (selectedMock?.response && typeof selectedMock.response === 'object') {
            const resp = selectedMock.response;
            if (resp.peserta && (params.nomor || params.nokartu || params.no_kartu)) {
                const inputNo = params.nomor || params.nokartu || params.no_kartu;
                if (params.tipe === '2') {
                    resp.peserta.nik = inputNo;
                } else {
                    resp.peserta.noKartu = inputNo;
                }
            }
            if (params.no_sep || params.nosep) {
                resp.noSep = params.no_sep || params.nosep;
            }
            if (params.kodebooking) {
                resp.kodebooking = params.kodebooking;
            }
        }

        result.value = {
            type: 'mock',
            status: Number(selectedMock?.metaData?.code ?? selectedMock?.metadata?.code ?? 200),
            timeMs: null,
            headers: {},
            request: buildPreview(),
            body: selectedMock,
        };
        hasExecuted.value = false;
        executedAt.value = null;
        isFresh.value = false;
        generateEncryptionPreview();
    }
};

// Switch active endpoint and update
const selectEndpoint = (id) => {
    activeId.value = id;
    error.value = null;

    if (activeEndpoint.value) {
        if (activeEndpoint.value.method === 'GET' && activeEndpoint.value.fields?.length) {
            requestTab.value = 'params';
        } else if (activeEndpoint.value.mode === 'form') {
            requestTab.value = 'form';
        } else {
            requestTab.value = 'json';
        }
    }

    if (executionMode.value === 'mock') {
        loadMockDataImmediately();
    } else {
        result.value = null;
        hasExecuted.value = false;
        executedAt.value = null;
    }
};

// Auto-select first endpoint when module changes
watch(selectedModule, (newMod) => {
    if (newMod === 'all') return;
    const current = activeEndpoint.value;
    if (!current || current.groupId !== newMod) {
        const firstInMod = flattened.find((item) => item.groupId === newMod);
        if (firstInMod) {
            selectEndpoint(firstInMod.id);
        }
    }
});

watch(mockScenario, () => {
    if (executionMode.value === 'mock') {
        loadMockDataImmediately();
    }
});

watch(executionMode, (mode) => {
    if (mode === 'mock') {
        loadMockDataImmediately();
    } else {
        result.value = null;
        hasExecuted.value = false;
        executedAt.value = null;
    }
});

// Watch input changes to mark unexecuted state
watch([queryState, formState, jsonState], () => {
    hasExecuted.value = false;
}, { deep: true });

watch(requestTab, (tab) => {
    if (tab === 'req_encrypted') {
        generateRequestCipherPreview();
    }
});

onMounted(() => {
    loadMockDataImmediately();
});

const runEndpoint = async () => {
    const endpoint = activeEndpoint.value;
    if (!endpoint || loading.value) return;

    // Check trial limit for demo/trial users
    if (!isUnlimited.value && quotaUsed.value >= quotaLimit.value) {
        showQuotaModal.value = true;
        return;
    }

    const recordQuota = (status = 200, time = 150) => {
        if (!isUnlimited.value) {
            quotaUsed.value++;
            localStorage.setItem(quotaKey, String(quotaUsed.value));
        }

        // Persistent Server Telemetry Logging
        axios.post('/admin/bpjs-tester/log-simulation', {
            endpoint: endpoint.path || '/api/v1/bpjs/simulator',
            method: endpoint.method || 'GET',
            status_code: status,
            duration_ms: time,
            payload: buildPreview(),
            summary: executionMode.value === 'mock' ? 'Uji Mock Simulator' : 'Uji Live Gateway',
        }).catch(() => {});
    };

    loading.value = true;
    error.value = null;
    loadingStep.value = 1;
    loadingMessage.value = 'Memvalidasi parameter & skenario pengujian...';

    if (executionMode.value === 'mock') {
        const simulatedMs = Math.floor(Math.random() * 200) + 650; // 650ms - 850ms realistic developer simulator latency

        window.setTimeout(() => {
            if (loading.value) {
                loadingStep.value = 2;
                loadingMessage.value = `Mengeksekusi simulator engine ${endpoint.groupLabel}...`;
            }
        }, Math.floor(simulatedMs * 0.35));

        window.setTimeout(() => {
            if (loading.value) {
                loadingStep.value = 3;
                loadingMessage.value = 'Mendekripsi respons AES-256 & verifikasi signature...';
            }
        }, Math.floor(simulatedMs * 0.7));

        window.setTimeout(() => {
            const rawMock = endpoint.mockResponses?.[mockScenario.value] ?? endpoint.mockResponses?.success ?? {
                metaData: { code: '200', message: 'Sukses' },
                response: {},
            };

            // Clone and enrich with user's current parameter inputs so mock data reflects what user typed
            const selectedMock = JSON.parse(JSON.stringify(rawMock));
            const preview = buildPreview();
            const params = preview?.params ?? preview ?? {};

            if (selectedMock?.response && typeof selectedMock.response === 'object') {
                const resp = selectedMock.response;
                if (resp.peserta && (params.nomor || params.nokartu || params.no_kartu)) {
                    const inputNo = params.nomor || params.nokartu || params.no_kartu;
                    if (params.tipe === '2') {
                        resp.peserta.nik = inputNo;
                    } else {
                        resp.peserta.noKartu = inputNo;
                    }
                }
                if (params.no_sep || params.nosep) {
                    resp.noSep = params.no_sep || params.nosep;
                }
                if (params.kodebooking) {
                    resp.kodebooking = params.kodebooking;
                }
            }

            result.value = {
                type: 'mock',
                status: Number(selectedMock?.metaData?.code ?? selectedMock?.metadata?.code ?? 200),
                timeMs: simulatedMs,
                headers: {
                    'x-simulation-time': `${simulatedMs}ms`,
                    'x-scenario': mockScenario.value,
                    'x-signature': 'MOCK-VERIFIED-HMAC-SHA256',
                },
                request: buildPreview(),
                body: selectedMock,
            };
            generateEncryptionPreview();
            recordQuota(result.value?.status || 200, simulatedMs);
            loading.value = false;
            hasExecuted.value = true;
            executedAt.value = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB';
            isFresh.value = true;
            window.setTimeout(() => {
                isFresh.value = false;
            }, 2500);
        }, simulatedMs);
        return;
    }

    // Live API Execution
    const start = performance.now();
    try {
        let response;
        let url = endpoint.path;

        window.setTimeout(() => {
            if (loading.value) {
                loadingStep.value = 2;
                loadingMessage.value = 'Mengirim HTTP request ke gateway server BPJS...';
            }
        }, 200);

        // Substitute path params
        if (endpoint.fields && queryState[endpoint.id]) {
            Object.entries(queryState[endpoint.id]).forEach(([k, v]) => {
                url = url.replace(`{${k}}`, encodeURIComponent(String(v)));
            });
        }

        if (endpoint.mode === 'query') {
            response = await axios.request({
                method: endpoint.method,
                url: url,
                params: queryState[endpoint.id] ?? {},
            });
        } else if (endpoint.mode === 'form') {
            response = await axios.request({
                method: endpoint.method,
                url: url,
                data: buildPreview(),
                headers: { 'Content-Type': 'application/json' },
            });
        } else {
            const parsed = JSON.parse(jsonState[endpoint.id] ?? '{}');
            response = await axios.request({
                method: endpoint.method,
                url: url,
                data: parsed,
                headers: { 'Content-Type': 'application/json' },
            });
        }

        const elapsedMs = Math.round(performance.now() - start);
        result.value = {
            type: 'live',
            status: response.status,
            timeMs: elapsedMs,
            headers: response.headers,
            request: buildPreview(),
            body: response.data,
        };
        generateEncryptionPreview();
        hasExecuted.value = true;
        executedAt.value = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB';
        isFresh.value = true;
        window.setTimeout(() => {
            isFresh.value = false;
        }, 2500);
    } catch (err) {
        const res = err.response;
        error.value = err.message;
        result.value = {
            type: 'live',
            status: res?.status ?? 500,
            timeMs: Math.round(performance.now() - start),
            headers: res?.headers ?? {},
            request: buildPreview(),
            body: res?.data ?? {
                metaData: { code: '500', message: err.message },
                response: {},
            },
        };
        hasExecuted.value = true;
        executedAt.value = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB';
    } finally {
        recordQuota(result.value?.status || 200, result.value?.timeMs || 150);
        loading.value = false;
    }
};

const getMethodBadgeClass = (method) => {
    switch (method) {
        case 'GET':
            return 'bg-sky-100 text-sky-800 border-sky-300';
        case 'POST':
            return 'bg-emerald-100 text-emerald-800 border-emerald-300';
        case 'PUT':
            return 'bg-amber-100 text-amber-800 border-amber-300';
        case 'DELETE':
            return 'bg-rose-100 text-rose-800 border-rose-300';
        default:
            return 'bg-slate-100 text-slate-800 border-slate-300';
    }
};
</script>

<template>
    <Head :title="`${systemLabel} API Studio`" />

    <AdminLayout>
        <!-- Module Header Navigation -->
        <div class="mb-3 rounded-lg border border-slate-200/90 bg-white p-2 shadow-xs">
            <div class="flex items-center gap-1.5 overflow-x-auto scrollbar-none">
                <button
                    v-for="tab in moduleTabs"
                    :key="tab.id"
                    type="button"
                    class="group flex shrink-0 items-center gap-2 rounded-md px-3 py-1.5 text-xs font-semibold transition"
                    :class="selectedModule === tab.id
                        ? 'bg-slate-900 text-white shadow-xs'
                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                    @click="selectedModule = tab.id"
                >
                    <span>{{ tab.label }}</span>
                    <span
                        class="rounded-full px-1.5 py-0.2 text-[10px] font-bold"
                        :class="selectedModule === tab.id
                            ? 'bg-slate-800 text-slate-200'
                            : 'bg-slate-100 text-slate-500 group-hover:bg-slate-200 group-hover:text-slate-700'"
                    >
                        {{ tab.count }}
                    </span>
                </button>
            </div>
        </div>

        <!-- Main Studio Grid -->
        <div class="grid grid-cols-1 gap-3 xl:grid-cols-[330px_minmax(0,1fr)]">
            <!-- LEFT PANEL: Endpoint Explorer -->
            <aside class="flex flex-col rounded-lg border border-slate-200 bg-white shadow-xs overflow-hidden xl:h-[calc(100vh-8.5rem)]">
                <!-- Search & Filters -->
                <div class="p-2.5 border-b border-slate-200 bg-slate-50/70 space-y-2">
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-2.5 top-2.5 h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Cari endpoint atau path..."
                            class="w-full rounded border border-slate-300 bg-white py-1.5 pl-8 pr-2.5 text-xs text-slate-900 placeholder:text-slate-400 outline-none focus:border-teal-600 focus:ring-1 focus:ring-teal-600 transition"
                        >
                    </div>

                    <div class="flex items-center justify-between gap-1 text-[10px]">
                        <div class="flex items-center gap-1">
                            <button
                                v-for="m in ['ALL', 'GET', 'POST', 'PUT', 'DELETE']"
                                :key="m"
                                type="button"
                                class="rounded px-1.5 py-0.5 font-bold uppercase transition"
                                :class="selectedMethod === m
                                    ? 'bg-slate-900 text-white'
                                    : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-100'"
                                @click="selectedMethod = m"
                            >
                                {{ m }}
                            </button>
                        </div>
                        <span class="font-medium text-slate-400">{{ filteredEndpoints.length }} item</span>
                    </div>
                </div>

                <!-- Endpoint Items -->
                <div class="flex-1 overflow-y-auto divide-y divide-slate-100">
                    <button
                        v-for="item in filteredEndpoints"
                        :key="item.id"
                        type="button"
                        class="w-full text-left px-3 py-2 transition text-xs flex flex-col gap-0.5 border-l-2"
                        :class="activeId === item.id
                            ? 'bg-teal-50/80 border-teal-600'
                            : 'border-transparent hover:bg-slate-50'"
                        @click="selectEndpoint(item.id)"
                    >
                        <div class="flex items-center gap-2">
                            <span
                                class="inline-flex rounded px-1 py-0.2 text-[9px] font-bold border shrink-0 uppercase"
                                :class="getMethodBadgeClass(item.method)"
                            >
                                {{ item.method }}
                            </span>
                            <span class="font-semibold text-slate-900 truncate flex-1">
                                {{ item.title }}
                            </span>
                        </div>

                        <p class="font-mono text-[10px] text-slate-500 truncate pl-0.5">
                            {{ item.path }}
                        </p>
                    </button>

                    <div v-if="filteredEndpoints.length === 0" class="p-6 text-center text-slate-500 text-xs">
                        Tidak ada endpoint yang cocok.
                    </div>
                </div>
            </aside>

            <!-- RIGHT PANEL: API Studio (Workbench) -->
            <section class="flex flex-col gap-3">
                <!-- Quota / Unlimited Status Bar -->
                <div v-if="isUnlimited" class="flex items-center justify-between rounded-lg border border-emerald-200 bg-emerald-50/70 px-3.5 py-2 text-xs">
                    <div class="flex items-center gap-2 text-emerald-800">
                        <svg class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span class="font-bold">Akses Semaunya (Unlimited Request):</span>
                        <span class="text-emerald-700 hidden sm:inline">Lisensi bulanan aktif tanpa batas harian. Bebas uji coba seluruh 209 endpoint.</span>
                    </div>
                    <span class="rounded bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-800 border border-emerald-300">
                        Akses Semaunya
                    </span>
                </div>

                <div
                    v-else
                    class="rounded-xl border p-3.5 text-xs transition-all shadow-xs"
                    :class="quotaUsed >= quotaLimit
                        ? 'border-rose-300 bg-rose-50/85 text-rose-950'
                        : 'border-amber-200 bg-amber-50/80 text-amber-950'"
                >
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <!-- Left Info -->
                        <div class="flex items-start sm:items-center gap-3">
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl transition"
                                :class="quotaUsed >= quotaLimit ? 'bg-rose-100 text-rose-700 ring-1 ring-rose-300' : 'bg-amber-100 text-amber-700 ring-1 ring-amber-300'"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-extrabold uppercase tracking-wide"
                                        :class="quotaUsed >= quotaLimit ? 'bg-rose-200/90 text-rose-900 border border-rose-300' : 'bg-amber-200/90 text-amber-900 border border-amber-300'"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="quotaUsed >= quotaLimit ? 'bg-rose-600 animate-pulse' : 'bg-amber-600'"></span>
                                        {{ quotaUsed >= quotaLimit ? 'Batas Uji Coba Harian Tercapai' : 'Mode Uji Coba Terbatas' }}
                                    </span>
                                    <span class="text-slate-700 font-semibold text-xs">
                                        Kuota Gratis: <strong class="font-bold text-slate-900">{{ quotaUsed }}</strong> / {{ quotaLimit }} request per hari
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-600 flex items-center gap-1.5">
                                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span>Reset otomatis:</span>
                                    <strong class="font-bold text-slate-900">{{ nextResetInfo.remainingStr }}</strong>
                                    <span class="text-slate-500">({{ nextResetInfo.dateStr }} pukul {{ nextResetInfo.timeStr }})</span>
                                </p>
                            </div>
                        </div>

                        <!-- Right Actions -->
                        <div class="flex items-center gap-3 shrink-0 self-end sm:self-center">
                            <!-- Clean Progress Meter -->
                            <div class="hidden md:flex flex-col items-end gap-1">
                                <div class="flex items-center gap-1.5 text-[10px] font-mono">
                                    <span class="text-slate-500">Pemakaian:</span>
                                    <span class="font-bold text-slate-900">{{ quotaUsed }}/{{ quotaLimit }} ({{ Math.min(100, Math.round((quotaUsed / (quotaLimit || 1)) * 100)) }}%)</span>
                                </div>
                                <div class="w-28 bg-slate-200 rounded-full h-2 overflow-hidden ring-1 ring-slate-300/60">
                                    <div
                                        class="h-2 rounded-full transition-all duration-500"
                                        :class="quotaUsed >= quotaLimit ? 'bg-rose-600' : 'bg-amber-600'"
                                        :style="{ width: `${Math.min(100, (quotaUsed / (quotaLimit || 1)) * 100)}%` }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Upgrade Button -->
                            <a
                                :href="waUpgradeUrl"
                                target="_blank"
                                class="inline-flex items-center gap-2 rounded-lg bg-teal-800 px-3.5 py-2 text-xs font-bold text-white shadow-xs hover:bg-teal-900 transition hover:shadow-sm"
                            >
                                <div class="flex flex-col text-left leading-tight">
                                    <span class="text-[10px] font-medium text-teal-200">Ingin Akses Tanpa Batas?</span>
                                    <span class="font-bold">Akses Semaunya ({{ formatRibuanSingkat(settings.pricing_monthly_price ?? 450000) }})</span>
                                </div>
                                <span v-if="settings.pricing_monthly_promo_active && settings.pricing_monthly_promo_badge" class="rounded bg-amber-400 px-1.5 py-0.5 text-[9px] font-black text-slate-900 leading-none">
                                    PROMO
                                </span>
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Request Control Bar -->
                <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-xs space-y-2.5">
                    <!-- Row 1: Method + URL + Send Button (Clean, Non-overflowing Postman Style) -->
                    <div class="flex items-center gap-2">
                        <!-- URL Input Container -->
                        <div class="flex flex-1 items-center rounded-lg border border-slate-300 bg-slate-50/80 overflow-hidden text-xs focus-within:border-teal-600 focus-within:ring-2 focus-within:ring-teal-500/20 transition">
                            <span
                                class="px-3 py-2 font-mono font-black text-xs border-r border-slate-200 shrink-0"
                                :class="getMethodBadgeClass(activeEndpoint?.method)"
                            >
                                {{ activeEndpoint?.method }}
                            </span>
                            <span class="px-2 text-slate-400 font-mono text-[11px] select-none shrink-0">
                                <svg class="h-3.5 w-3.5 text-slate-400 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                            </span>
                            <input
                                type="text"
                                :value="requestTargetUrl"
                                readonly
                                class="flex-1 py-2 font-mono text-slate-800 bg-transparent outline-none truncate select-all text-xs"
                            />
                            <button
                                type="button"
                                class="px-3 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-200/70 border-l border-slate-200 transition flex items-center gap-1 shrink-0"
                                @click="copyText('URL Endpoint', requestTargetUrl)"
                                title="Salin URL Endpoint"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <span>Copy</span>
                            </button>
                        </div>

                        <!-- Primary Action Button with Dynamic Loading State -->
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-xs font-bold text-white shadow-sm transition disabled:cursor-not-allowed disabled:opacity-80 shrink-0"
                            :class="loading
                                ? 'bg-teal-800 ring-2 ring-teal-400 ring-offset-1'
                                : executionMode === 'mock'
                                    ? 'bg-teal-700 hover:bg-teal-800 active:scale-98'
                                    : 'bg-sky-700 hover:bg-sky-800 active:scale-98'"
                            :disabled="loading"
                            @click="runEndpoint"
                        >
                            <svg v-if="loading" class="h-4 w-4 animate-spin text-teal-200" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <svg v-else-if="executionMode === 'mock'" class="h-4 w-4 text-teal-200" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                            <svg v-else class="h-4 w-4 text-sky-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                            <span>{{ loading ? (executionMode === 'mock' ? 'Menjalankan Mock...' : 'Mengirim Live...') : executionMode === 'mock' ? 'Uji Mock' : 'Kirim Live' }}</span>
                        </button>
                    </div>

                    <!-- Row 2: Secondary Controls (Mode Toggle, Scenario, Endpoint Description) -->
                    <div class="flex flex-wrap items-center justify-between gap-3 text-xs border-t border-slate-100 pt-2">
                        <div class="flex flex-wrap items-center gap-3">
                            <!-- Mode Segmented Control -->
                            <div class="inline-flex rounded-lg border border-slate-200 bg-slate-100 p-0.5 font-semibold text-xs shadow-2xs">
                                <button
                                    type="button"
                                    class="rounded-md px-2.5 py-1 transition flex items-center gap-1.5"
                                    :class="executionMode === 'mock'
                                        ? 'bg-white text-slate-900 shadow-xs font-bold'
                                        : 'text-slate-600 hover:text-slate-900'"
                                    @click="executionMode = 'mock'"
                                >
                                    <span>⚡ Mock Simulator (Dummy)</span>
                                </button>
                                <button
                                    type="button"
                                    class="rounded-md px-2.5 py-1 transition flex items-center gap-1.5"
                                    :class="executionMode === 'live'
                                        ? 'bg-teal-700 text-white shadow-xs font-bold'
                                        : 'text-slate-600 hover:text-slate-900'"
                                    @click="executionMode = 'live'"
                                >
                                    <span>🌐 Live API (Vendor BPJS)</span>
                                </button>
                            </div>

                            <!-- Mock Scenario Dropdown -->
                            <div v-if="executionMode === 'mock'" class="flex items-center gap-1.5 text-slate-600">
                                <span class="text-xs font-medium text-slate-500">Skenario:</span>
                                <select
                                    v-model="mockScenario"
                                    class="rounded-md border border-slate-300 bg-white px-2 py-1 text-xs font-semibold text-slate-800 outline-none hover:border-slate-400 focus:border-teal-500 transition"
                                >
                                    <option value="success">200 Sukses (Data Normal)</option>
                                    <option value="failed">400 Error (Gagal Simulasi)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Active Endpoint Info -->
                        <div class="flex items-center gap-2 text-xs text-slate-600 truncate max-w-lg">
                            <span class="rounded bg-slate-100 px-2 py-0.5 font-bold text-slate-800 border border-slate-200 truncate">
                                {{ activeEndpoint?.title }}
                            </span>
                            <span class="text-slate-400">•</span>
                            <span class="truncate text-slate-500 font-medium">{{ activeEndpoint?.description }}</span>
                        </div>
                    </div>
                </div>

                <!-- Interactive Architecture Flow Diagram (Bagaimana SIMRS Gateway Bekerja) -->
                <div class="rounded-xl border border-teal-200/90 bg-gradient-to-r from-teal-50/70 via-white to-sky-50/70 p-3 shadow-xs">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-teal-600 text-white text-[11px] font-bold">
                                ℹ️
                            </span>
                            <h4 class="text-xs font-bold text-slate-900">
                                Alur Kerja Bridging BPJS (Cara SIMRS Gateway Menghilangkan Kerumitan Kriptografi)
                            </h4>
                            <span class="rounded bg-teal-100 px-1.5 py-0.2 text-[10px] font-bold text-teal-800 border border-teal-200">
                                Diagram Visual
                            </span>
                        </div>
                        <button
                            type="button"
                            class="text-[11px] font-semibold text-teal-700 hover:text-teal-900 transition flex items-center gap-1"
                            @click="showArchitectureFlow = !showArchitectureFlow"
                        >
                            <span>{{ showArchitectureFlow ? 'Sembunyikan' : 'Tampilkan Diagram' }}</span>
                            <svg class="h-3.5 w-3.5 transition-transform" :class="showArchitectureFlow ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </div>

                    <div v-show="showArchitectureFlow" class="grid grid-cols-1 md:grid-cols-4 gap-2 pt-1 transition-all">
                        <!-- Step 1 -->
                        <div
                            class="rounded-lg border p-2.5 transition cursor-pointer"
                            :class="requestTab === 'params' || requestTab === 'form' || requestTab === 'json'
                                ? 'border-emerald-400 bg-white shadow-xs ring-1 ring-emerald-300'
                                : 'border-slate-200/80 bg-white/70 hover:bg-white'"
                            @click="requestTab = 'params'"
                        >
                            <div class="flex items-center justify-between text-[11px] mb-1 font-bold">
                                <span class="text-emerald-700 flex items-center gap-1">
                                    <span>🏢 1. SIMRS Client</span>
                                </span>
                                <span class="rounded bg-emerald-100 px-1.5 py-0.2 text-[9px] font-mono text-emerald-800">Plain JSON</span>
                            </div>
                            <p class="text-[10px] text-slate-600 leading-snug">
                                Aplikasi SIMRS Anda cukup kirim parameter biasa (NIK, No Kartu, SEP) tanpa pusing rumus enkripsi.
                            </p>
                        </div>

                        <!-- Step 2 -->
                        <div
                            class="rounded-lg border p-2.5 transition cursor-pointer"
                            :class="requestTab === 'req_encrypted' || requestTab === 'headers'
                                ? 'border-teal-500 bg-white shadow-xs ring-1 ring-teal-300'
                                : 'border-slate-200/80 bg-white/70 hover:bg-white'"
                            @click="requestTab = 'headers'"
                        >
                            <div class="flex items-center justify-between text-[11px] mb-1 font-bold">
                                <span class="text-teal-700 flex items-center gap-1">
                                    <span>🛡️ 2. Security Gateway</span>
                                </span>
                                <span class="rounded bg-teal-100 px-1.5 py-0.2 text-[9px] font-mono text-teal-800">HMAC+AES</span>
                            </div>
                            <p class="text-[10px] text-slate-600 leading-snug">
                                Gateway otomatis generate Header BPJS (ConsID, Timestamp, Signature SHA256) & enkripsi payload.
                            </p>
                        </div>

                        <!-- Step 3 -->
                        <div
                            class="rounded-lg border p-2.5 transition cursor-pointer"
                            :class="responseTab === 'encrypted'
                                ? 'border-sky-500 bg-white shadow-xs ring-1 ring-sky-300'
                                : 'border-slate-200/80 bg-white/70 hover:bg-white'"
                            @click="responseTab = 'encrypted'"
                        >
                            <div class="flex items-center justify-between text-[11px] mb-1 font-bold">
                                <span class="text-sky-700 flex items-center gap-1">
                                    <span>🏥 3. Server BPJS</span>
                                </span>
                                <span class="rounded bg-sky-100 px-1.5 py-0.2 text-[9px] font-mono text-sky-800">Wire Cipher</span>
                            </div>
                            <p class="text-[10px] text-slate-600 leading-snug">
                                Server BPJS memproses dan mengembalikan respons terenkripsi (Ciphertext LZ-String) via internet.
                            </p>
                        </div>

                        <!-- Step 4 -->
                        <div
                            class="rounded-lg border p-2.5 transition cursor-pointer"
                            :class="responseTab === 'plain'
                                ? 'border-emerald-500 bg-white shadow-xs ring-1 ring-emerald-300'
                                : 'border-slate-200/80 bg-white/70 hover:bg-white'"
                            @click="responseTab = 'plain'"
                        >
                            <div class="flex items-center justify-between text-[11px] mb-1 font-bold">
                                <span class="text-emerald-700 flex items-center gap-1">
                                    <span>✨ 4. Siap Digunakan</span>
                                </span>
                                <span class="rounded bg-emerald-100 px-1.5 py-0.2 text-[9px] font-mono text-emerald-800">Decrypted</span>
                            </div>
                            <p class="text-[10px] text-slate-600 leading-snug">
                                Gateway otomatis mendekripsi ciphertext menjadi JSON bersih yang langsung dipakai aplikasi SIMRS.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Two-Column Workbench (Request Builder & Response Console) -->
                <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                    <!-- LEFT COLUMN: Request Builder -->
                    <div class="flex flex-col rounded-xl border border-slate-200 bg-white shadow-xs overflow-hidden h-[540px]">
                        <!-- Request Tabs Header (2-tier clean layout) -->
                        <div class="border-b border-slate-200 bg-slate-50/90 text-xs">
                            <!-- Tier 1: Title & Quick Actions -->
                            <div class="flex items-center justify-between px-3.5 py-2 border-b border-slate-200/60">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-800 text-xs">Request Builder</span>
                                    <span class="rounded px-1.5 py-0.5 font-mono text-[10px] font-bold" :class="getMethodBadgeClass(activeEndpoint?.method)">
                                        {{ activeEndpoint?.method }}
                                    </span>
                                    <span class="text-[10px] text-slate-500 font-mono">
                                        {{ activeEndpoint?.mode === 'json' ? 'JSON Body' : activeEndpoint?.mode === 'form' ? 'Form Data' : 'URL Query' }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 text-[11px]">
                                    <button
                                        v-if="requestTab === 'json'"
                                        type="button"
                                        class="font-semibold text-teal-700 hover:text-teal-900 transition flex items-center gap-1"
                                        @click="formatJsonEditor"
                                    >
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                                        </svg>
                                        <span>Format JSON</span>
                                    </button>
                                    <button
                                        type="button"
                                        class="font-semibold text-slate-600 hover:text-slate-900 transition flex items-center gap-1"
                                        @click="copyText('Request Payload', buildPreview())"
                                        title="Salin Data Request"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                        <span>Salin</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Tier 2: Navigation Tabs -->
                            <div class="flex items-center gap-1 px-3 py-1.5 overflow-x-auto">
                                <button
                                    v-if="activeEndpoint?.fields?.length"
                                    type="button"
                                    class="rounded-md px-2.5 py-1 text-xs font-semibold transition flex items-center gap-1.5 shrink-0"
                                    :class="requestTab === 'params'
                                        ? 'bg-white text-slate-900 border border-slate-200 shadow-2xs font-bold'
                                        : 'text-slate-600 hover:text-slate-900'"
                                    @click="requestTab = 'params'"
                                >
                                    <span>⚙️ Parameters</span>
                                    <span class="rounded bg-slate-100 px-1.5 py-0.2 text-[10px] font-mono text-slate-600">{{ activeEndpoint.fields.length }}</span>
                                </button>

                                <button
                                    v-if="activeEndpoint?.mode === 'form'"
                                    type="button"
                                    class="rounded-md px-2.5 py-1 text-xs font-semibold transition flex items-center gap-1.5 shrink-0"
                                    :class="requestTab === 'form'
                                        ? 'bg-white text-slate-900 border border-slate-200 shadow-2xs font-bold'
                                        : 'text-slate-600 hover:text-slate-900'"
                                    @click="requestTab = 'form'"
                                >
                                    <span>📋 Form Fields</span>
                                </button>

                                <button
                                    type="button"
                                    class="rounded-md px-2.5 py-1 text-xs font-semibold transition flex items-center gap-1.5 shrink-0"
                                    :class="requestTab === 'json'
                                        ? 'bg-white text-slate-900 border border-slate-200 shadow-2xs font-bold'
                                        : 'text-slate-600 hover:text-slate-900'"
                                    @click="requestTab = 'json'"
                                >
                                    <span>📝 Body (Sebelum Enkripsi)</span>
                                </button>

                                <button
                                    type="button"
                                    class="rounded-md px-2.5 py-1 text-xs font-semibold transition flex items-center gap-1.5 shrink-0"
                                    :class="requestTab === 'req_encrypted'
                                        ? 'bg-white text-slate-900 border border-slate-200 shadow-2xs font-bold'
                                        : 'text-slate-600 hover:text-slate-900'"
                                    @click="requestTab = 'req_encrypted'"
                                >
                                    <span>🔒 Payload Wire (Sesudah Enkripsi)</span>
                                </button>

                                <button
                                    type="button"
                                    class="rounded-md px-2.5 py-1 text-xs font-semibold transition flex items-center gap-1.5 shrink-0"
                                    :class="requestTab === 'headers'
                                        ? 'bg-white text-slate-900 border border-slate-200 shadow-2xs font-bold'
                                        : 'text-slate-600 hover:text-slate-900'"
                                    @click="requestTab = 'headers'"
                                >
                                    <span>🛡️ Headers BPJS</span>
                                </button>
                            </div>
                        </div>

                        <!-- Request Tab Body -->
                        <div class="p-3 overflow-y-auto flex-1">
                            <!-- Tab 1: Parameter Table with Pre-filled Data -->
                            <div v-if="requestTab === 'params' && activeEndpoint?.fields?.length">
                                <div
                                    class="mb-2.5 flex items-center justify-between rounded px-2.5 py-1.5 text-[11px] transition"
                                    :class="hasExecuted ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-amber-50/90 text-amber-800 border border-amber-200'"
                                >
                                    <span class="flex items-center gap-1.5">
                                        <span class="h-2 w-2 rounded-full" :class="hasExecuted ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                                        <span>{{ hasExecuted ? 'Parameter berhasil diuji dengan respons mock simulator' : 'Parameter siap disimulasikan. Klik "Uji Mock" untuk menjalankan.' }}</span>
                                    </span>
                                    <span v-if="hasExecuted && executedAt" class="font-mono text-[10px] text-emerald-700">
                                        {{ executedAt }}
                                    </span>
                                </div>
                                <table class="w-full text-left text-xs">
                                    <thead>
                                        <tr class="border-b border-slate-200 text-slate-400 uppercase text-[10px] font-bold">
                                            <th class="py-1.5 pr-2">Parameter</th>
                                            <th class="py-1.5 px-2">Tipe</th>
                                            <th class="py-1.5 pl-2">Nilai Input (Dummy / Real)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="field in activeEndpoint.fields" :key="field.key">
                                            <td class="py-1.5 pr-2 font-mono font-semibold text-slate-700">
                                                {{ field.key }}
                                                <span class="block font-sans text-[10px] font-normal text-slate-400">{{ field.label }}</span>
                                            </td>
                                            <td class="py-1.5 px-2 text-slate-400 font-mono text-[10px]">
                                                {{ field.type }}
                                            </td>
                                            <td class="py-1.5 pl-2">
                                                <input
                                                    v-model="queryState[activeEndpoint.id][field.key]"
                                                    type="text"
                                                    class="w-full rounded border border-slate-300 bg-white px-2 py-1 text-xs text-slate-900 font-mono outline-none focus:border-teal-600 focus:ring-1 focus:ring-teal-600"
                                                >
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Tab 2: Form Fields -->
                            <div v-else-if="requestTab === 'form' && activeEndpoint?.mode === 'form'" class="grid gap-2.5 sm:grid-cols-2">
                                <div v-for="field in activeEndpoint.formFields" :key="field.path" class="space-y-0.5">
                                    <label class="block text-[11px] font-semibold text-slate-700">{{ field.label }}</label>
                                    <input
                                        v-model="formState[activeEndpoint.id][field.path]"
                                        type="text"
                                        class="w-full rounded border border-slate-300 bg-white px-2 py-1 text-xs font-mono outline-none focus:border-teal-600"
                                    >
                                </div>
                            </div>

                            <!-- Tab 3: JSON Code Editor (Sebelum Enkripsi) -->
                            <div v-else-if="requestTab === 'json'" class="h-full flex flex-col">
                                <div class="mb-1.5 flex items-center justify-between text-[11px] text-slate-500">
                                    <span class="flex items-center gap-1 text-emerald-700 font-semibold">
                                        <span>🔓</span> Request Body Sebelum Enkripsi (Clean JSON)
                                    </span>
                                    <span>Format yang dikirim aplikasi SIMRS Anda</span>
                                </div>
                                <textarea
                                    v-model="jsonState[activeEndpoint.id]"
                                    class="w-full flex-1 rounded border border-slate-700 bg-slate-950 p-2.5 font-mono text-xs text-emerald-300 outline-none focus:border-teal-500 leading-relaxed resize-none"
                                    placeholder="{}"
                                />
                            </div>

                            <!-- Tab 3B: Request Payload Sesudah Enkripsi (Wire Format) -->
                            <div v-else-if="requestTab === 'req_encrypted'" class="h-full flex flex-col space-y-2">
                                <div class="rounded border border-teal-200 bg-teal-50/70 p-2 text-[11px] text-teal-900 flex items-start gap-2">
                                    <svg class="h-4 w-4 text-teal-600 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                    </svg>
                                    <div>
                                        <strong class="font-bold">Format Transmisi ke BPJS (Wire Payload):</strong>
                                        <p class="text-[10px] text-slate-600 mt-0.5">
                                            Request HTTP dikirim ke gateway BPJS bersama Signature HMAC-SHA256. Jika endpoint mensyaratkan enkripsi payload, gateway mengonversi otomatis ke AES-256 + LZ-String.
                                        </p>
                                    </div>
                                </div>

                                <div class="flex-1 rounded border border-slate-800 bg-slate-950 p-2.5 font-mono text-xs overflow-y-auto">
                                    <div class="text-teal-400 font-bold mb-1">// HTTP Request Headers Otomatis:</div>
                                    <div class="text-slate-400 mb-2 pb-2 border-b border-slate-800 text-[11px] space-y-0.5">
                                        <div>X-cons-id: <span class="text-emerald-300">{{ vendorDebugHeaders['X-cons-id'] }}</span></div>
                                        <div>X-timestamp: <span class="text-emerald-300">{{ vendorDebugHeaders['X-timestamp'] }}</span></div>
                                        <div>X-signature: <span class="text-emerald-300">{{ vendorDebugHeaders['X-signature'] }}</span></div>
                                        <div>user_key: <span class="text-emerald-300">{{ vendorDebugHeaders['user_key'] }}</span></div>
                                    </div>

                                    <div class="text-teal-400 font-bold mb-1">// Request Body Wire:</div>
                                    <pre class="text-teal-300 text-[11px] leading-relaxed overflow-x-auto whitespace-pre">{{ requestCipherData?.raw_bpjs_response ? pretty(requestCipherData.raw_bpjs_response) : pretty(buildPreview() ?? {}) }}</pre>
                                </div>
                            </div>

                            <!-- Tab 4: Security Headers Preview -->
                            <div v-else-if="requestTab === 'headers'" class="space-y-2 text-xs">
                                <p class="text-slate-500 text-[11px]">Header BPJS otomatis dihitung menggunakan HMAC-SHA256.</p>
                                <div class="rounded border border-slate-200 divide-y divide-slate-100 font-mono text-xs">
                                    <div class="flex justify-between p-2">
                                        <span class="text-slate-500">X-cons-id</span>
                                        <span class="font-semibold text-slate-900">{{ vendorDebugHeaders['X-cons-id'] }}</span>
                                    </div>
                                    <div class="flex justify-between p-2">
                                        <span class="text-slate-500">X-timestamp</span>
                                        <span class="font-semibold text-slate-900">{{ vendorDebugHeaders['X-timestamp'] }}</span>
                                    </div>
                                    <div class="flex justify-between p-2">
                                        <span class="text-slate-500">X-signature</span>
                                        <span class="font-semibold text-slate-900">{{ vendorDebugHeaders['X-signature'] }}</span>
                                    </div>
                                    <div class="flex justify-between p-2">
                                        <span class="text-slate-500">user_key</span>
                                        <span class="font-semibold text-slate-900">{{ vendorDebugHeaders['user_key'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: Response Inspector with Live Simulation Loading Console & Fresh Data Indicator -->
                    <div
                        class="relative flex flex-col rounded-xl border bg-white shadow-xs overflow-hidden h-[540px] transition-all duration-500"
                        :class="isFresh ? 'border-emerald-500 ring-2 ring-emerald-500/40 shadow-sm' : 'border-slate-200'"
                    >
                        <!-- Top Progress Bar Animation -->
                        <div v-if="loading" class="absolute top-0 left-0 right-0 h-1 z-30 overflow-hidden bg-teal-100">
                            <div class="h-full bg-teal-600 animate-loading-bar w-full"></div>
                        </div>
                        <div v-else-if="isFresh" class="absolute top-0 left-0 right-0 h-0.5 z-30 bg-emerald-500 transition-all duration-1000"></div>

                        <!-- Response Header (2-tier clean layout) -->
                        <div class="border-b border-slate-200 bg-slate-50/90 text-xs">
                            <!-- Tier 1: Telemetry & Status Bar -->
                            <div class="flex items-center justify-between px-3.5 py-2 border-b border-slate-200/60 flex-wrap gap-2">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-bold text-slate-800 text-xs flex items-center gap-1.5">
                                        <svg class="h-3.5 w-3.5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span>Response Console</span>
                                    </span>

                                    <!-- Loading State -->
                                    <div v-if="loading" class="flex items-center gap-1.5">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-bold text-amber-700 ring-1 ring-inset ring-amber-600/30">
                                            <svg class="h-3 w-3 animate-spin text-amber-600" viewBox="0 0 24 24" fill="none">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                            </svg>
                                            {{ executionMode === 'mock' ? 'Simulasi Mock...' : 'Kirim Request...' }}
                                        </span>
                                        <span class="text-[10px] text-slate-400 font-mono animate-pulse">Menghitung...</span>
                                    </div>

                                    <!-- Executed or Preview State -->
                                    <template v-else>
                                        <!-- Status Code Badge -->
                                        <span
                                            v-if="hasExecuted"
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-bold ring-1 ring-inset"
                                            :class="(result?.status >= 200 && result?.status < 300)
                                                ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/30'
                                                : 'bg-rose-50 text-rose-700 ring-rose-600/30'"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full" :class="(result?.status >= 200 && result?.status < 300) ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                            {{ result?.status ?? 200 }} {{ result?.status === 200 ? 'OK' : 'Response' }}
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600 ring-1 ring-inset ring-slate-300"
                                        >
                                            Preview Mock
                                        </span>

                                        <!-- Latency / Duration -->
                                        <span v-if="result?.timeMs" class="inline-flex items-center gap-1 text-[11px] text-slate-600 font-mono font-medium bg-slate-100/80 px-2 py-0.5 rounded border border-slate-200">
                                            <span class="text-slate-400">⏱️</span> {{ result.timeMs }} ms
                                        </span>

                                        <!-- Payload Size -->
                                        <span class="inline-flex items-center gap-1 text-[10px] text-slate-500 font-mono bg-slate-100/60 px-2 py-0.5 rounded border border-slate-200/80">
                                            <span>📦</span> {{ payloadSize }}
                                        </span>

                                        <!-- Mode Indicator (Mock vs Live) -->
                                        <span
                                            class="rounded px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                            :class="result?.type === 'live' ? 'bg-teal-100 text-teal-800 border border-teal-200' : 'bg-amber-100 text-amber-800 border border-amber-200'"
                                        >
                                            {{ result?.type === 'live' ? '🌐 Live BPJS' : '⚡ Mock Simulator' }}
                                        </span>

                                        <!-- Fresh / Execution Timestamp Badge -->
                                        <span
                                            v-if="hasExecuted && executedAt"
                                            class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold transition"
                                            :class="isFresh ? 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-400 font-bold' : 'bg-slate-100 text-slate-600'"
                                        >
                                            <svg v-if="isFresh" class="h-3 w-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                            {{ isFresh ? 'Baru Dihasilkan!' : executedAt }}
                                        </span>
                                    </template>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-[11px] font-semibold text-slate-700 bg-white border border-slate-200 shadow-2xs hover:bg-slate-50 hover:text-slate-900 transition active:scale-95"
                                        @click="copyText('Response Body', responseTab === 'encrypted' ? rawBpjsResponseFormatted : (result?.body ?? {}))"
                                        title="Salin Data Respons Aktif"
                                    >
                                        <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                        <span>Salin Respons</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Tier 2: View Switcher Tabs -->
                            <div class="flex items-center justify-between px-3 py-1.5 bg-slate-100/70 overflow-x-auto gap-2">
                                <div class="flex items-center gap-1 shrink-0">
                                    <!-- Tab 1: Sebelum Enkripsi -->
                                    <button
                                        type="button"
                                        class="rounded-md px-2.5 py-1 text-xs font-semibold transition flex items-center gap-1.5 shrink-0"
                                        :class="(responseTab === 'plain' || responseTab === 'json')
                                            ? 'bg-white text-slate-900 border border-slate-200 shadow-2xs font-bold'
                                            : 'text-slate-600 hover:text-slate-900'"
                                        @click="responseTab = 'plain'"
                                    >
                                        <span>🔓 Sebelum Enkripsi (Plain JSON)</span>
                                    </button>

                                    <!-- Tab 2: Sesudah Enkripsi (Raw BPJS) -->
                                    <button
                                        type="button"
                                        class="rounded-md px-2.5 py-1 text-xs font-semibold transition flex items-center gap-1.5 shrink-0"
                                        :class="responseTab === 'encrypted'
                                            ? 'bg-white text-slate-900 border border-slate-200 shadow-2xs font-bold'
                                            : 'text-slate-600 hover:text-slate-900'"
                                        @click="responseTab = 'encrypted'"
                                    >
                                        <span class="inline-block h-2 w-2 rounded-full" :class="encryptedPayloadData ? 'bg-teal-500 animate-pulse' : 'bg-slate-400'"></span>
                                        <span>🔒 Sesudah Enkripsi (Raw BPJS)</span>
                                    </button>

                                    <!-- Tab 3: Bandingkan -->
                                    <button
                                        type="button"
                                        class="rounded-md px-2.5 py-1 text-xs font-semibold transition hidden sm:inline-flex items-center gap-1.5 shrink-0"
                                        :class="responseTab === 'compare'
                                            ? 'bg-white text-slate-900 border border-slate-200 shadow-2xs font-bold'
                                            : 'text-slate-600 hover:text-slate-900'"
                                        @click="responseTab = 'compare'"
                                    >
                                        <span>⚖️ Bandingkan (Split View)</span>
                                    </button>

                                    <!-- Tab 4: Table View -->
                                    <button
                                        v-if="responseSections.length > 0"
                                        type="button"
                                        class="rounded-md px-2.5 py-1 text-xs font-semibold transition flex items-center gap-1.5 shrink-0"
                                        :class="responseTab === 'table'
                                            ? 'bg-white text-slate-900 border border-slate-200 shadow-2xs font-bold'
                                            : 'text-slate-600 hover:text-slate-900'"
                                        @click="responseTab = 'table'"
                                    >
                                        <span>📊 Table View</span>
                                        <span class="rounded bg-slate-200/80 px-1.5 py-0.2 text-[10px] font-mono font-bold text-slate-700">{{ responseSections.length }}</span>
                                    </button>
                                </div>

                                <div class="hidden md:flex items-center gap-2 text-[11px] text-slate-500 font-medium">
                                    <span v-if="responseTab === 'plain' || responseTab === 'json'" class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200/60 font-mono text-[10px]">
                                        Format SIMRS (Decrypted)
                                    </span>
                                    <span v-else-if="responseTab === 'encrypted'" class="text-teal-700 bg-teal-50 px-2 py-0.5 rounded border border-teal-200/60 font-mono text-[10px]">
                                        AES-256-CBC + LZ-String
                                    </span>
                                    <span v-else-if="responseTab === 'compare'" class="text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-200/60 font-mono text-[10px]">
                                        Side-by-side Inspection
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Response Body Viewer (with Dedicated Loading Console & Skeleton) -->
                        <div class="flex-1 overflow-y-auto bg-slate-950/95 text-emerald-300 relative">
                            <!-- LOADING CONSOLE & SKELETON STATE -->
                            <div v-if="loading" class="h-full flex flex-col justify-between p-4 font-mono text-xs text-slate-300 bg-slate-950">
                                <div class="space-y-3.5">
                                    <!-- Console Status Bar -->
                                    <div class="flex items-center justify-between border-b border-slate-800 pb-2 text-[11px] text-slate-400">
                                        <div class="flex items-center gap-2">
                                            <div class="h-2 w-2 rounded-full bg-teal-400 animate-ping"></div>
                                            <span class="font-bold text-teal-400 tracking-wider">MOCK SIMULATOR RUNNING</span>
                                        </div>
                                        <span class="font-mono text-slate-400 font-semibold">Tahap {{ loadingStep }}/3</span>
                                    </div>

                                    <!-- Telemetry log stream -->
                                    <div class="space-y-2 text-xs">
                                        <div class="flex items-center gap-2 text-slate-300">
                                            <span class="text-teal-400 font-bold">&gt;</span>
                                            <span class="text-slate-400">Target:</span>
                                            <span class="text-emerald-400 font-mono">{{ activeEndpoint?.method }} {{ requestTargetUrl }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-slate-400">
                                            <span class="text-teal-400 font-bold">&gt;</span>
                                            <span>Skenario:</span>
                                            <span class="text-amber-300 font-semibold font-mono">{{ mockScenario === 'success' ? '200 OK (Data Sukses)' : '400 Bad Request (Simulasi Error)' }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-teal-300 font-semibold">
                                            <svg class="h-3.5 w-3.5 animate-spin text-teal-400 shrink-0" viewBox="0 0 24 24" fill="none">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                            </svg>
                                            <span>{{ loadingMessage }}</span>
                                        </div>
                                    </div>

                                    <!-- Shimmering JSON Skeleton Lines -->
                                    <div class="pt-3 space-y-2 opacity-75">
                                        <div class="h-3 bg-slate-800 rounded w-1/5 animate-pulse"></div>
                                        <div class="h-3 bg-slate-800/80 rounded w-1/2 animate-pulse pl-4"></div>
                                        <div class="h-3 bg-slate-800/70 rounded w-3/4 animate-pulse pl-8"></div>
                                        <div class="h-3 bg-slate-800/60 rounded w-2/3 animate-pulse pl-8"></div>
                                        <div class="h-3 bg-slate-800/50 rounded w-1/3 animate-pulse pl-4"></div>
                                        <div class="h-3 bg-slate-800/40 rounded w-1/6 animate-pulse"></div>
                                    </div>
                                </div>

                                <!-- Console Footer -->
                                <div class="flex items-center justify-between border-t border-slate-800/80 pt-2 text-[10px] text-slate-500 font-sans">
                                    <span>Memproses payload respons & dekripsi token HMAC...</span>
                                    <span class="font-mono text-teal-400 font-semibold animate-pulse">Mohon tunggu...</span>
                                </div>
                            </div>

                            <!-- COMPLETED / PREVIEW DATA VIEW -->
                            <template v-else>
                                <!-- 1. Sebelum Enkripsi (Clean Decrypted JSON) -->
                                <div v-if="responseTab === 'plain' || responseTab === 'json'" class="h-full flex flex-col">
                                    <div class="border-b border-slate-800 bg-slate-900/90 px-3 py-2 flex items-center justify-between text-[11px] text-slate-400">
                                        <div class="flex items-center gap-2 text-emerald-400 font-semibold">
                                            <div class="p-1 rounded bg-emerald-950/80 border border-emerald-800/80 text-emerald-400">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <span class="font-bold text-emerald-300">Sebelum Enkripsi / Hasil Dekripsi (Plain JSON)</span>
                                                <span class="block text-[10px] text-slate-400 font-normal">Format yang langsung dikonsumsi aplikasi SIMRS Anda</span>
                                            </div>
                                        </div>
                                        <span class="rounded bg-emerald-950/90 border border-emerald-700/60 px-2 py-0.5 text-[10px] font-mono font-bold text-emerald-300">
                                            OUTPUT SIMRS
                                        </span>
                                    </div>
                                    <div class="p-3 flex-1 overflow-y-auto">
                                        <pre class="font-mono text-xs leading-relaxed overflow-x-auto whitespace-pre text-emerald-300">{{ pretty(result?.body ?? {}) }}</pre>
                                    </div>
                                </div>

                                <!-- 2. Sesudah Enkripsi (Raw Ciphertext BPJS) -->
                                <div v-else-if="responseTab === 'encrypted'" class="h-full flex flex-col">
                                    <div class="border-b border-slate-800 bg-slate-900/95 px-3 py-2 space-y-2 text-[11px]">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <div class="flex items-center gap-2 text-teal-400 font-bold">
                                                <div class="p-1 rounded bg-teal-950/80 border border-teal-800/80 text-teal-400">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <span class="text-teal-300 font-bold">Sesudah Enkripsi (Payload Asli BPJS di Wire Internet)</span>
                                                    <span class="block text-[10px] text-slate-400 font-normal">Transmisi data aman berstandar kriptografi BPJS Kesehatan</span>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <button
                                                    type="button"
                                                    class="rounded bg-teal-950 border border-teal-800/80 px-2.5 py-1 text-[10px] font-semibold text-teal-300 hover:bg-teal-900 transition active:scale-95"
                                                    @click="copyCiphertextOnly"
                                                >
                                                    Salin Ciphertext Saja
                                                </button>
                                                <button
                                                    type="button"
                                                    class="rounded bg-slate-800 px-2.5 py-1 text-[10px] font-semibold text-slate-300 hover:bg-slate-700 transition active:scale-95"
                                                    @click="copyText('Raw BPJS Payload', rawBpjsResponseFormatted)"
                                                >
                                                    Salin JSON BPJS Utuh
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Cryptographic parameter badges -->
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 font-mono text-[10px]">
                                            <div class="rounded bg-slate-950 p-2 border border-slate-800/70">
                                                <div class="text-slate-500 text-[9px] uppercase font-sans">Metode Cipher</div>
                                                <div class="text-emerald-400 font-bold truncate">{{ encryptedPayloadData?.crypto_details?.algorithm ?? 'AES-256-CBC' }}</div>
                                            </div>
                                            <div class="rounded bg-slate-950 p-2 border border-slate-800/70">
                                                <div class="text-slate-500 text-[9px] uppercase font-sans">Kompresi</div>
                                                <div class="text-amber-400 font-bold truncate">{{ encryptedPayloadData?.crypto_details?.compression ?? 'LZ-String' }}</div>
                                            </div>
                                            <div class="rounded bg-slate-950 p-2 border border-slate-800/70">
                                                <div class="text-slate-500 text-[9px] uppercase font-sans">Formula Kunci</div>
                                                <div class="text-sky-400 font-bold truncate" title="ConsID + SecretKey + Timestamp">ConsID+Secret+Time</div>
                                            </div>
                                            <div class="rounded bg-slate-950 p-2 border border-slate-800/70">
                                                <div class="text-slate-500 text-[9px] uppercase font-sans">Ukuran Data Wire</div>
                                                <div class="text-teal-300 font-bold truncate">
                                                    {{ encryptedPayloadData?.crypto_details?.plain_byte_size ?? 0 }} B ➔ {{ encryptedPayloadData?.crypto_details?.encrypted_byte_size ?? 0 }} B
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-3 flex-1 overflow-y-auto">
                                        <pre class="font-mono text-xs leading-relaxed overflow-x-auto whitespace-pre text-teal-300">{{ rawBpjsResponseFormatted }}</pre>
                                    </div>
                                </div>

                                <!-- 3. Bandingkan (Split View: Sebelum vs Sesudah) -->
                                <div v-else-if="responseTab === 'compare'" class="h-full flex flex-col">
                                    <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-800 flex-1 overflow-hidden">
                                        <!-- Left: Sebelum Enkripsi -->
                                        <div class="flex flex-col h-full overflow-hidden">
                                            <div class="border-b border-slate-800 bg-slate-900/90 px-2.5 py-1.5 flex items-center justify-between text-[11px] text-emerald-400">
                                                <span class="font-bold flex items-center gap-1">
                                                    <span>🔓</span> Sebelum Enkripsi (Clean JSON)
                                                </span>
                                                <button
                                                    type="button"
                                                    class="text-[10px] text-slate-400 hover:text-white"
                                                    @click="copyText('Clean JSON', result?.body ?? {})"
                                                >
                                                    Salin
                                                </button>
                                            </div>
                                            <div class="p-2.5 flex-1 overflow-y-auto">
                                                <pre class="font-mono text-[11px] leading-relaxed overflow-x-auto whitespace-pre text-emerald-300">{{ pretty(result?.body ?? {}) }}</pre>
                                            </div>
                                        </div>

                                        <!-- Right: Sesudah Enkripsi -->
                                        <div class="flex flex-col h-full overflow-hidden">
                                            <div class="border-b border-slate-800 bg-slate-900/90 px-2.5 py-1.5 flex items-center justify-between text-[11px] text-teal-400">
                                                <span class="font-bold flex items-center gap-1">
                                                    <span>🔒</span> Sesudah Enkripsi (Raw BPJS)
                                                </span>
                                                <button
                                                    type="button"
                                                    class="text-[10px] text-teal-300 hover:text-white"
                                                    @click="copyText('Raw BPJS', rawBpjsResponseFormatted)"
                                                >
                                                    Salin
                                                </button>
                                            </div>
                                            <div class="p-2.5 flex-1 overflow-y-auto">
                                                <pre class="font-mono text-[11px] leading-relaxed overflow-x-auto whitespace-pre text-teal-300">{{ rawBpjsResponseFormatted }}</pre>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 4. Table View (for array responses) -->
                                <div v-else-if="responseTab === 'table'" class="space-y-3 bg-white p-3 text-slate-800">
                                    <div v-for="section in responseSections" :key="section.key" class="border border-slate-200 rounded overflow-hidden">
                                        <div class="bg-slate-50 px-3 py-1.5 border-b border-slate-200 flex items-center justify-between text-xs">
                                            <span class="font-bold text-slate-800 uppercase">{{ section.key }}</span>
                                            <span class="text-[10px] text-slate-500 font-semibold">{{ section.rows.length }} baris data</span>
                                        </div>
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-left text-xs">
                                                <thead>
                                                    <tr class="bg-slate-50/70 border-b border-slate-200 text-slate-600 font-semibold">
                                                        <th v-for="col in section.columns" :key="col" class="p-2 border-r border-slate-100 last:border-r-0 whitespace-nowrap">
                                                            {{ col }}
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100">
                                                    <tr v-for="(row, rIdx) in section.rows" :key="rIdx" class="hover:bg-slate-50/50">
                                                        <td v-for="col in section.columns" :key="col" class="p-2 border-r border-slate-100 last:border-r-0 whitespace-nowrap font-mono text-[11px] text-slate-800">
                                                            {{ row[col] !== null && row[col] !== undefined ? row[col] : '-' }}
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Footer Info Bar -->
                        <div class="border-t border-slate-200 bg-slate-50 px-3 py-1.5 text-[10px] text-slate-500 flex items-center justify-between">
                            <span class="truncate">
                                <strong class="text-slate-700">Status Engine:</strong>
                                <span v-if="loading" class="text-amber-600 font-semibold ml-1">Sedang menjalankan pengujian simulasi...</span>
                                <span v-else-if="hasExecuted" class="text-emerald-700 font-semibold ml-1">Data simulasi berhasil dihasilkan sesuai parameter input</span>
                                <span v-else class="text-slate-500 ml-1">Mode Mock Preview (Klik "Uji Mock" untuk mengeksekusi parameter)</span>
                            </span>
                            <span v-if="copiedState" class="font-semibold text-emerald-600">
                                {{ copiedState }}
                            </span>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Quota Reached Modal (50/50 Limit) -->
        <div v-if="showQuotaModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs">
            <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl space-y-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-amber-700 shrink-0">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Batas Uji Coba Harian Tercapai ({{ quotaLimit }}/{{ quotaLimit }})</h3>
                        <p class="text-xs text-slate-500">Kuota gratis {{ quotaLimit }} request untuk hari ini telah habis.</p>
                    </div>
                </div>

                <p class="text-xs text-slate-600 leading-relaxed">
                    Untuk uji coba selanjutnya atau kebutuhan integrasi tanpa batas harian, silakan aktifkan <strong>Lisensi Bulanan ({{ formatRupiah(settings.pricing_monthly_price ?? 450000) }} / bulan)</strong> untuk mendapatkan <strong>Akses Semaunya (Unlimited Request)</strong> ke seluruh 209 endpoint.
                </p>

                <div class="rounded-xl border border-rose-200 bg-rose-50/80 p-3 space-y-1 text-xs">
                    <div class="flex items-center justify-between font-bold text-rose-900">
                        <span>Waktu Reset Kuota Uji Coba:</span>
                        <span class="rounded bg-rose-200 px-1.5 py-0.5 text-[10px] text-rose-900 font-mono font-bold">00:00 WIB</span>
                    </div>
                    <p class="text-[11px] text-rose-800 leading-snug">
                        Sistem akan mereset dan membuka kembali {{ quotaLimit }} request uji coba pada:
                        <strong class="font-bold text-slate-900 block mt-0.5">
                            {{ nextResetInfo.dateStr }} pukul {{ nextResetInfo.timeStr }} ({{ nextResetInfo.remainingStr }})
                        </strong>
                    </p>
                </div>

                <div class="rounded-xl border border-teal-200 bg-teal-50/70 p-3 space-y-2 text-xs">
                    <div class="flex items-center justify-between font-bold text-teal-900">
                        <div class="flex items-center gap-1.5">
                            <span>Lisensi Bulanan (Akses Semaunya)</span>
                            <span v-if="settings.pricing_monthly_promo_active && settings.pricing_monthly_promo_badge" class="rounded bg-rose-600 px-1.5 py-0.5 text-[10px] font-extrabold text-white animate-pulse">
                                {{ settings.pricing_monthly_promo_badge }}
                            </span>
                        </div>
                        <div class="text-right">
                            <span v-if="settings.pricing_monthly_promo_active && settings.pricing_monthly_strike_price" class="text-[11px] text-slate-400 line-through mr-1 block">
                                {{ formatRupiah(settings.pricing_monthly_strike_price) }}
                            </span>
                            <span class="text-sm font-extrabold text-teal-800">{{ formatRupiah(settings.pricing_monthly_price ?? 450000) }} / bln</span>
                        </div>
                    </div>
                    <div v-if="settings.pricing_monthly_promo_active && settings.pricing_monthly_promo_code" class="rounded-lg bg-amber-100/80 border border-amber-300 p-2 flex items-center justify-between text-[11px] text-amber-950 font-medium">
                        <span>Kupon Promo: <strong class="font-mono font-bold text-amber-900 bg-white px-1.5 py-0.5 rounded border border-amber-200">{{ settings.pricing_monthly_promo_code }}</strong></span>
                        <span class="text-[10px] text-amber-800 font-bold">Diskon Terpasang</span>
                    </div>
                    <ul class="text-[11px] text-teal-800 space-y-1">
                        <li>✓ Unlimited request tanpa batas kuota {{ quotaLimit }}/hari</li>
                        <li>✓ Akses penuh simulator & live proxy 209 endpoint</li>
                        <li>✓ Akun aktif 30 hari penuh</li>
                    </ul>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition"
                        @click="showQuotaModal = false"
                    >
                        Tutup
                    </button>
                    <a
                        :href="waUpgradeUrl"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-teal-800 px-4 py-2 text-xs font-bold text-white hover:bg-teal-900 transition shadow-xs"
                    >
                        <span>Akses Semaunya via WhatsApp</span>
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
