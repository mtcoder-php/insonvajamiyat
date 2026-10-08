<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import type { AiRequestTypeValue } from '@/types';
import { studios } from './aiMeta';
import { t, tk } from '@/lib/i18n';

/** Natija hali yo'q: xizmat haqida qisqa ma'lumot */
const props = defineProps<{ type: AiRequestTypeValue; ready: boolean }>();

const studio = computed(() => studios[props.type]);

const hints: Record<AiRequestTypeValue, string[]> = {
    spell_check: [
        tk("Xatolar asl matnda belgilanadi, tuzatilgani yonida ko'rinadi"),
        tk('Har bir taklifni alohida qabul yoki rad etasiz'),
        tk('Yakuniy matnni nusxalab yoki saqlab olasiz'),
    ],
    translation: [
        tk("So'zma-so'z emas — ilmiy uslub va terminologiya saqlanadi"),
        tk('Tarjimani shu yerda tahrirlab, versiya sifatida saqlaysiz'),
        tk('Istalgan versiyani Word (.docx) formatida yuklab olasiz'),
    ],
    analysis: [
        tk(
            "Ilmiy uslub, aniqlik, tuzilish, terminologiya va bog'liqlik bahosi",
        ),
        tk('Kuchli tomonlar va kamchiliklar'),
        tk("Matnni yaxshilash bo'yicha aniq tavsiyalar"),
    ],
};
</script>

<template>
    <div
        class="flex min-h-72 flex-col items-center justify-center gap-4 rounded-xl border border-dashed border-line bg-[#fafcff] px-6 py-10 text-center"
    >
        <span
            :class="
                cn(
                    'flex size-14 items-center justify-center rounded-2xl bg-gradient-to-br text-white shadow-md',
                    studio.gradient,
                )
            "
        >
            <component :is="studio.icon" class="size-6" />
        </span>
        <div>
            <p class="font-sans text-base font-bold text-navy-950">
                {{
                    ready
                        ? "Natija shu yerda ko'rinadi"
                        : 'AI xizmati hali sozlanmagan'
                }}
            </p>
            <p class="mt-1 max-w-sm text-xs text-navy-500">
                {{
                    ready
                        ? t(
                              'Chapdagi formaga matnni joylashtiring va boshlang.',
                          )
                        : "API kaliti va model Sozlamalar bo'limida kiritilgach ishga tushadi."
                }}
            </p>
        </div>
        <ul v-if="ready" class="grid gap-1.5 text-left text-xs text-navy-600">
            <li
                v-for="hint in hints[type]"
                :key="hint"
                class="flex items-start gap-2"
            >
                <span
                    class="mt-1.5 size-1.5 shrink-0 rounded-full bg-brand-500"
                />
                {{ t(hint) }}
            </li>
        </ul>
    </div>
</template>
