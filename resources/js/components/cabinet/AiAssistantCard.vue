<script setup lang="ts">
import { ArrowRight, Bot, Sparkles } from '@lucide/vue';
import { t } from '@/lib/i18n';

/**
 * "AI yordamchi" — AI laboratoriya moduli tayyor bo'lgach havolaga aylanadi.
 */
</script>

<template>
    <section
        class="group relative isolate overflow-hidden rounded-xl border border-brand-100 bg-gradient-to-br from-brand-50 via-white to-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_16px_32px_-18px_rgba(0,108,246,0.45)]"
    >
        <Sparkles
            class="absolute top-3 right-3 size-5 text-brand-300 transition-transform duration-500 group-hover:rotate-12"
            aria-hidden="true"
        />
        <div class="flex items-start gap-3">
            <span
                class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-navy-950 text-white shadow-[0_10px_20px_-12px_rgba(0,30,60,0.9)] transition-transform duration-300 group-hover:-translate-y-0.5 group-hover:rotate-[-4deg]"
            >
                <Bot class="size-6" />
            </span>
            <div class="min-w-0">
                <h2 class="font-sans text-[15px] font-bold text-navy-950">
                    {{ t('AI yordamchi') }}
                </h2>
                <p class="mt-1 text-xs leading-relaxed text-navy-600">
                    {{
                        t(
                            'Maqola yozish, imlo va uslubni tekshirishda sizga yordam beradi.',
                        )
                    }}
                </p>
            </div>
        </div>
        <button
            type="button"
            disabled
            :title="t('Tez orada')"
            class="mt-4 inline-flex w-full cursor-not-allowed items-center justify-center gap-1.5 rounded-lg border border-brand-200 bg-white px-3 py-2 text-xs font-semibold text-brand-700"
        >
            {{ t('AI laboratoriya') }}
            <span
                class="rounded bg-brand-50 px-1.5 py-0.5 text-[10px] font-medium"
                >{{ t('tez orada') }}</span
            >
            <ArrowRight class="size-3.5" />
        </button>
    </section>
</template>
