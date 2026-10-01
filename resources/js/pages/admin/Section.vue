<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CircleDashed, Hammer } from '@lucide/vue';
import { dashboard } from '@/routes/admin';

/**
 * Hali ishlab chiqilmagan admin bo'limi: vazifasi va keyingi bosqich rejasi.
 * Bo'lim CRUD'i tayyor bo'lgach, o'z sahifasi bilan almashtiriladi.
 */
defineProps<{
    section: {
        key: string;
        title: string;
        description: string;
        features: string[];
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Admin panel', href: dashboard() }],
    },
});
</script>

<template>
    <Head :title="section.title" />

    <div class="flex-1 bg-[#f5f7fb] p-4 md:p-6">
        <div
            class="mx-auto max-w-3xl overflow-hidden rounded-2xl border border-line bg-white shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
        >
            <div
                class="relative isolate bg-navy-950 px-6 py-8 text-white sm:px-8"
            >
                <div
                    class="absolute inset-0 -z-10 bg-girih opacity-[0.06]"
                    aria-hidden="true"
                />
                <div
                    class="absolute -top-16 -right-10 -z-10 size-56 rounded-full bg-brand-500/25 blur-3xl"
                    aria-hidden="true"
                />
                <span
                    class="inline-flex items-center gap-1.5 rounded-full bg-gold-500/15 px-3 py-1 text-xs font-semibold text-gold-300 ring-1 ring-gold-400/30"
                >
                    <Hammer class="size-3.5" />
                    Ishlab chiqilmoqda
                </span>
                <h1
                    class="mt-4 font-sans text-2xl font-bold text-white sm:text-3xl"
                >
                    {{ section.title }}
                </h1>
                <p class="mt-2 max-w-xl text-sm leading-relaxed text-white/75">
                    {{ section.description }}
                </p>
            </div>

            <div class="px-6 py-6 sm:px-8">
                <h2 class="font-sans text-sm font-bold text-navy-950">
                    Bu bo'limda bo'ladi
                </h2>
                <ul class="mt-3 grid gap-2 sm:grid-cols-2">
                    <li
                        v-for="feature in section.features"
                        :key="feature"
                        class="flex items-start gap-2.5 rounded-lg border border-line px-3 py-2.5 text-[13px] text-navy-700 transition-colors hover:border-brand-200 hover:bg-brand-50/40"
                    >
                        <CircleDashed
                            class="mt-0.5 size-4 shrink-0 text-brand-500"
                        />
                        {{ feature }}
                    </li>
                </ul>

                <Link
                    :href="dashboard()"
                    class="group mt-6 inline-flex items-center gap-2 text-sm font-semibold text-brand-700 hover:text-brand-600"
                >
                    <ArrowLeft
                        class="size-4 transition-transform group-hover:-translate-x-0.5"
                    />
                    Bosh sahifaga qaytish
                </Link>
            </div>
        </div>
    </div>
</template>
