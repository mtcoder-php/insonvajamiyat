<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, BrainCircuit } from '@lucide/vue';
import { studios } from '@/components/ai/aiMeta';
import { formatDateTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { ArticleAiSummary } from '@/types';
import { t } from '@/lib/i18n';

/**
 * Maqola sahifasidagi "AI Studio" bloki: shu maqolaga biriktirilgan so'nggi AI natijalari
 * va maqola tanlangan holda AI Studio'ni ochish.
 */
defineProps<{ ai: ArticleAiSummary }>();
</script>

<template>
    <section
        class="relative isolate overflow-hidden rounded-xl border border-line bg-white p-5 shadow-[0_1px_2px_rgba(0,30,60,0.05)] transition-shadow duration-300 hover:shadow-[0_12px_32px_-18px_rgba(0,36,66,0.25)]"
    >
        <div
            class="absolute -top-10 -right-10 -z-10 size-32 rounded-full bg-gradient-to-br from-brand-100 to-violet-100 opacity-70 blur-2xl"
            aria-hidden="true"
        />
        <h2
            class="flex items-center gap-2 font-sans text-[15px] font-bold text-navy-950"
        >
            <span
                class="flex size-7 items-center justify-center rounded-lg bg-gradient-to-br from-brand-500 to-violet-600 text-white"
            >
                <BrainCircuit class="size-4" />
            </span>
            AI Studio
        </h2>
        <p class="mt-2 text-xs leading-relaxed text-navy-600">
            {{
                t(
                    "Annotatsiya yoki matnni imlo va uslub bo'yicha tekshiring, ingliz/rus tiliga ilmiy tarjima qiling.",
                )
            }}
        </p>

        <ul v-if="ai.requests.length" class="mt-3 grid grid-cols-1 gap-1">
            <li v-for="item in ai.requests" :key="item.uuid">
                <Link
                    :href="item.url"
                    class="group flex items-center gap-2.5 rounded-lg px-2 py-1.5 transition-colors hover:bg-brand-50/60"
                >
                    <span
                        :class="
                            cn(
                                'flex size-7 shrink-0 items-center justify-center rounded-md',
                                studios[item.type].soft,
                            )
                        "
                    >
                        <component
                            :is="studios[item.type].icon"
                            class="size-3.5"
                        />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span
                            class="block truncate text-[12.5px] font-medium text-navy-900 group-hover:text-brand-700"
                            >{{ item.studio }} · {{ item.languages }}</span
                        >
                        <span class="block text-[11px] text-navy-400"
                            >{{ formatDateTime(item.createdAt) }} ·
                            {{ item.statusLabel }}</span
                        >
                    </span>
                </Link>
            </li>
        </ul>

        <Link
            :href="ai.url"
            class="group mt-3 inline-flex h-9 w-full items-center justify-center gap-2 rounded-lg bg-brand-50 text-[13px] font-semibold text-brand-700 transition-all hover:-translate-y-px hover:bg-brand-100"
        >
            {{ t("AI Studio'da ochish") }}
            <ArrowRight
                class="size-4 transition-transform group-hover:translate-x-0.5"
            />
        </Link>
    </section>
</template>
