<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    ClipboardCheck,
    FileSearch,
    Globe,
    Send,
} from '@lucide/vue';
import SectionHeading from '@/components/web/SectionHeading.vue';
import { guidelines } from '@/routes';

/**
 * Nashr jarayoni — TZ'dagi maqola hayotiy sikli qisqacha (4 bosqich).
 */
const steps = [
    {
        title: 'Maqola yuborish',
        text: "Shaxsiy kabinet orqali onlayn shaklni to'ldirib, qo'lyozmani yuklaysiz.",
        icon: Send,
    },
    {
        title: "Dastlabki ko'rib chiqish",
        text: 'Muharrir maqolani jurnal talablari va mavzuga muvofiqligini tekshiradi.',
        icon: FileSearch,
    },
    {
        title: 'Taqriz',
        text: 'Soha mutaxassislari ikki tomonlama yashirin taqriz asosida baholaydi.',
        icon: ClipboardCheck,
    },
    {
        title: 'Nashr',
        text: 'Qabul qilingan maqola DOI bilan jurnal sonida ochiq nashr etiladi.',
        icon: Globe,
    },
];
</script>

<template>
    <section class="bg-white py-16 lg:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <SectionHeading
                eyebrow="Mualliflar uchun"
                title="Nashr jarayoni"
                description="Maqola yuborilgandan nashr etilguncha barcha bosqichlar shaffof — holatini kabinetingizda kuzatib borasiz."
                :href="guidelines()"
                link-text="To'liq yo'riqnoma"
            />
            <ol
                class="relative grid gap-8 md:grid-cols-2 lg:grid-cols-4 lg:gap-6"
            >
                <div
                    class="absolute top-6 right-[12%] left-[12%] hidden h-px bg-gradient-to-r from-brand-200 via-brand-300 to-brand-200 lg:block"
                    aria-hidden="true"
                />
                <li
                    v-for="(step, index) in steps"
                    :key="step.title"
                    class="relative"
                >
                    <div
                        class="flex items-center gap-4 lg:flex-col lg:items-start"
                    >
                        <span
                            class="relative flex size-12 shrink-0 items-center justify-center rounded-full border border-brand-200 bg-white text-brand-700 shadow-card"
                        >
                            <component :is="step.icon" class="size-5" />
                            <span
                                class="absolute -top-1 -right-1 flex size-5 items-center justify-center rounded-full bg-navy-900 text-[10px] font-bold text-white"
                            >
                                {{ index + 1 }}
                            </span>
                        </span>
                        <h3
                            class="font-serif text-lg font-semibold text-navy-950 lg:mt-2"
                        >
                            {{ step.title }}
                        </h3>
                    </div>
                    <p class="mt-2 text-sm leading-relaxed text-navy-600">
                        {{ step.text }}
                    </p>
                </li>
            </ol>
            <div class="mt-10 flex justify-center lg:hidden">
                <Link
                    :href="guidelines()"
                    class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700"
                >
                    To'liq yo'riqnoma
                    <ArrowRight class="size-4" />
                </Link>
            </div>
        </div>
    </section>
</template>
