<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    defaultEmail: {
        type: String,
        default: '',
    },
    defaultPassword: {
        type: String,
        default: '',
    },
});

const form = useForm({
    email: props.defaultEmail,
    password: props.defaultPassword,
});

const submit = () => {
    form.post('/login');
};
</script>

<template>
    <Head title="Admin Login" />

    <div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-[var(--app-canvas)] px-4 py-12">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(13,148,136,0.18),_transparent_30%),radial-gradient(circle_at_bottom_right,_rgba(251,191,36,0.16),_transparent_35%)]" />

        <div class="relative grid w-full max-w-5xl gap-6 lg:grid-cols-[1.15fr_0.85fr]">
            <section class="rounded-[32px] border border-white/60 bg-slate-950 p-8 text-white shadow-[0_24px_80px_rgba(15,23,42,0.28)]">
                <p class="text-xs font-semibold uppercase tracking-[0.4em] text-[var(--app-sand)]">SIMRS API</p>
                <h1 class="mt-4 text-4xl font-semibold leading-tight">
                    Admin console untuk menguji integrasi BPJS dari satu tempat.
                </h1>
                <p class="mt-4 max-w-xl text-sm leading-7 text-slate-300">
                    Gunakan panel ini untuk mencoba endpoint BPJS yang sudah tersedia, baik dengan request live ke service asli
                    maupun mock preview untuk simulasi cepat.
                </p>

                <div class="mt-8 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Login Default</p>
                        <p class="mt-2 text-sm font-medium">admin@simrs.local</p>
                        <p class="text-sm text-slate-300">password</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Cakupan</p>
                        <p class="mt-2 text-sm text-slate-200">VClaim, referensi, monitoring, surat kontrol, dan SPRI.</p>
                    </div>
                </div>
            </section>

            <section class="rounded-[32px] border border-white/60 bg-white/85 p-8 shadow-[0_24px_80px_rgba(15,23,42,0.12)] backdrop-blur">
                <p class="text-xs font-semibold uppercase tracking-[0.35em] text-[var(--app-accent)]">Admin Login</p>
                <h2 class="mt-3 text-2xl font-semibold tracking-tight text-slate-900">Masuk ke dashboard tester</h2>
                <p class="mt-2 text-sm text-slate-600">Akses dibatasi untuk admin agar pengujian API tetap terkontrol.</p>

                <form class="mt-8 space-y-5" @submit.prevent="submit">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Email</label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-[var(--app-accent)] focus:ring-4 focus:ring-emerald-100"
                        >
                        <p v-if="form.errors.email" class="mt-2 text-sm text-rose-600">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Password</label>
                        <input
                            v-model="form.password"
                            type="password"
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-[var(--app-accent)] focus:ring-4 focus:ring-emerald-100"
                        >
                        <p v-if="form.errors.password" class="mt-2 text-sm text-rose-600">{{ form.errors.password }}</p>
                    </div>

                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center rounded-2xl bg-[var(--app-accent)] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[var(--app-accent-strong)] disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Memproses...' : 'Masuk sebagai Admin' }}
                    </button>
                </form>
            </section>
        </div>
    </div>
</template>
