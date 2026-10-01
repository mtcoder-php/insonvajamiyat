<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Construction, Send } from '@lucide/vue';
import DashCard from '@/components/admin/dashboard/DashCard.vue';
import CabinetPageHeader from '@/components/cabinet/CabinetPageHeader.vue';
import QuickActionsCard from '@/components/cabinet/QuickActionsCard.vue';
import SubmissionStepper from '@/components/cabinet/SubmissionStepper.vue';
import { create, index } from '@/routes/cabinet/articles';
import type { CabinetLinks } from '@/types';

/**
 * Yangi maqola yuborish — 7 bosqichli forma (keyingi bosqichda to'ldiriladi).
 */
defineProps<{ links: CabinetLinks }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Yangi maqola yuborish', href: create() }],
    },
});
</script>

<template>
    <Head title="Yangi maqola yuborish" />

    <div class="flex flex-col gap-5">
        <CabinetPageHeader
            title="Yangi maqola yuborish"
            description="Maqolangizni 7 bosqichda to'ldiring: ma'lumotlar, mualliflar, annotatsiya, kalit so'zlar va fayllar."
            :icon="Send"
            :breadcrumbs="[{ title: 'Yangi maqola yuborish', href: create() }]"
        />

        <SubmissionStepper :current="1" />

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_18rem]">
            <DashCard>
                <div class="flex flex-col items-center gap-3 py-10 text-center">
                    <span
                        class="flex size-14 items-center justify-center rounded-full bg-amber-50 text-amber-600"
                    >
                        <Construction class="size-6" />
                    </span>
                    <h2 class="font-sans text-lg font-bold text-navy-950">
                        Yuborish formasi tayyorlanmoqda
                    </h2>
                    <p class="max-w-md text-sm text-navy-600">
                        Tez orada maqolangizni shu yerdan bosqichma-bosqich
                        yuborishingiz mumkin bo'ladi. Hozircha maqola shablonini
                        yuklab olib, yo'riqnoma bilan tanishib chiqing.
                    </p>
                    <Link
                        :href="index()"
                        class="group mt-2 inline-flex items-center gap-1.5 rounded-lg border border-line px-4 py-2 text-xs font-semibold text-navy-700 transition-all hover:-translate-y-0.5 hover:border-brand-200 hover:text-brand-700"
                    >
                        <ArrowLeft
                            class="size-3.5 transition-transform group-hover:-translate-x-0.5"
                        />
                        Mening maqolalarim
                    </Link>
                </div>
            </DashCard>

            <QuickActionsCard :links="links" />
        </div>
    </div>
</template>
