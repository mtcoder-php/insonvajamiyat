<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import { computed } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import { dashboard, guidelines, register } from '@/routes';

/**
 * Mualliflarga chaqiruv kartasi (login sahifasidagi brend paneli uslubida).
 */
const { auth } = usePermissions();
const submitHref = computed(() => (auth.value.user ? dashboard() : register()));
</script>

<template>
    <section
        class="relative overflow-hidden rounded-xl bg-navy-gradient p-6 text-white"
    >
        <div
            class="absolute inset-0 bg-girih opacity-[0.06]"
            aria-hidden="true"
        />
        <div class="relative">
            <p
                class="text-[11px] font-semibold tracking-[0.16em] text-gold-300 uppercase"
            >
                Mualliflar uchun
            </p>
            <h3
                class="mt-2 font-serif text-xl leading-snug font-semibold text-white"
            >
                Tadqiqotingizni jurnalimizda nashr eting
            </h3>
            <p class="mt-2 text-sm leading-relaxed text-white/70">
                Maqolani onlayn yuboring, taqriz jarayonini shaxsiy kabinetda
                kuzatib boring.
            </p>
            <div class="mt-5 gold-rule w-16" />
            <Link
                :href="submitHref"
                class="mt-5 flex h-11 items-center justify-center gap-2 rounded-lg bg-brand-600 text-sm font-semibold transition-colors hover:bg-brand-500"
            >
                Maqola yuborish
                <ArrowRight class="size-4" />
            </Link>
            <Link
                :href="guidelines()"
                class="mt-3 block text-center text-sm font-medium text-white/75 hover:text-white"
            >
                Mualliflar uchun yo'riqnoma
            </Link>
        </div>
    </section>
</template>
