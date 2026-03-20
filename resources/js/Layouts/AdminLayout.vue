<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const user = computed(() => page.props.auth?.user ?? null);
const currentPath = computed(() => page.url ?? '');

const navItems = [
    { label: 'BPJS Tester', href: '/admin/bpjs-tester' },
    { label: 'SATUSEHAT Tester', href: '/admin/satusehat-tester' },
];

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <div class="min-h-screen bg-[var(--app-canvas)]">
        <div class="mx-auto flex min-h-screen w-full max-w-[1800px] flex-col px-5 py-6 lg:px-8 xl:px-10">
            <header class="mb-6 rounded-[28px] border border-white/60 bg-white/80 p-5 shadow-[0_20px_60px_rgba(15,23,42,0.08)] backdrop-blur">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.35em] text-[var(--app-accent)]">SIMRS Admin</p>
                        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">BPJS Integration Console</h1>
                        <p class="mt-1 max-w-2xl text-sm text-slate-600">
                            Panel admin untuk menguji endpoint BPJS dengan mode live maupun mock preview.
                        </p>
                    </div>

                    <div class="flex items-center gap-3 rounded-2xl bg-slate-900 px-4 py-3 text-white">
                        <div>
                            <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Login Admin</p>
                            <p class="text-sm font-medium">{{ user?.name }}</p>
                            <p class="text-xs text-slate-400">{{ user?.email }}</p>
                        </div>

                        <button
                            type="button"
                            class="rounded-xl border border-white/20 px-3 py-2 text-sm font-medium text-white transition hover:bg-white/10"
                            @click="logout"
                        >
                            Logout
                        </button>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap gap-2">
                    <a
                        v-for="item in navItems"
                        :key="item.href"
                        :href="item.href"
                        class="rounded-2xl px-4 py-2 text-sm font-semibold transition"
                        :class="currentPath.includes(item.href)
                            ? 'bg-[var(--app-accent)] text-white'
                            : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                    >
                        {{ item.label }}
                    </a>
                </div>
            </header>

            <main class="flex-1">
                <slot />
            </main>
        </div>
    </div>
</template>
