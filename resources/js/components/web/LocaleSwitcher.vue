<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Check, ChevronDown } from '@lucide/vue';
import LocaleFlag from '@/components/app/LocaleFlag.vue';
import { computed } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import { update } from '@/routes/locale';
import type { LocaleCode } from '@/types';

/**
 * Til tanlagich: bayroq + UZ ▾ → O'zbekcha / Русский / English (bayroqlar bilan).
 *   tone="dark"  — to'q ko'k topbar ichida
 *   tone="light" — oq fonda (mobil menyu)
 *   tone="glass" — admin header (bayroq, shaffof ramka)
 */
const props = withDefaults(
    defineProps<{ tone?: 'dark' | 'light' | 'glass' }>(),
    {
        tone: 'dark',
    },
);

const page = usePage();
const current = computed(() => page.props.locale);
const locales = computed(() => page.props.locales);

function select(code: LocaleCode): void {
    if (code === current.value) {
        return;
    }

    router.post(update.url(), { locale: code }, { preserveScroll: true });
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <button
                type="button"
                :class="
                    cn(
                        'group inline-flex items-center gap-1.5 text-xs font-semibold tracking-wide uppercase transition-all duration-300 outline-none focus-visible:ring-2',
                        props.tone === 'dark' &&
                            'h-7 rounded-md border border-white/20 bg-white/5 px-2 text-white hover:border-white/45 hover:bg-white/10 hover:shadow-[0_6px_16px_-8px_rgba(0,0,0,0.6)] focus-visible:ring-white/40 data-[state=open]:bg-white/15',
                        props.tone === 'light' &&
                            'h-8 rounded-md border border-line px-2 text-navy-800 hover:border-navy-200 hover:bg-navy-50 hover:shadow-sm focus-visible:ring-brand-200',
                        props.tone === 'glass' &&
                            'h-10 gap-2 rounded-lg border border-white/25 bg-white/5 px-3 text-sm text-white backdrop-blur-sm hover:-translate-y-px hover:border-white/50 hover:bg-white/10 hover:shadow-[0_8px_20px_-10px_rgba(0,0,0,0.6)] focus-visible:ring-white/40 data-[state=open]:bg-white/15',
                    )
                "
                :aria-label="`Sayt tili: ${current.toUpperCase()}`"
            >
                <LocaleFlag
                    :code="current"
                    :class="tone === 'glass' ? 'size-5' : 'size-4'"
                />
                {{ current }}
                <ChevronDown
                    class="size-3.5 opacity-70 transition-transform group-data-[state=open]:rotate-180"
                />
            </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent
            align="end"
            :side-offset="6"
            class="w-44 border-line bg-white p-1 text-navy-900 shadow-[0_16px_40px_-16px_rgba(0,30,60,0.35)]"
        >
            <DropdownMenuItem
                v-for="locale in locales"
                :key="locale.code"
                :class="
                    cn(
                        'cursor-pointer justify-between rounded-md px-3 py-2 text-sm transition-colors focus:bg-brand-50 focus:text-brand-700 data-[highlighted]:bg-brand-50 data-[highlighted]:text-brand-700',
                        locale.code === current
                            ? 'bg-navy-50 font-semibold text-navy-950'
                            : 'text-navy-700',
                    )
                "
                :lang="locale.code"
                @select="select(locale.code)"
            >
                <span class="flex items-center gap-2.5">
                    <LocaleFlag :code="locale.code" class="size-4" />
                    {{ locale.label }}
                </span>
                <Check
                    v-if="locale.code === current"
                    class="size-4 text-brand-600"
                />
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
