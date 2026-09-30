<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Check, ChevronDown, Globe } from '@lucide/vue';
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
 * Til tanlagich: 🌐 UZ ▾ → O'zbekcha / Русский / English.
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
                            'h-7 rounded-md px-2 text-white/90 hover:bg-white/10 hover:text-white focus-visible:ring-white/40 data-[state=open]:bg-white/10',
                        props.tone === 'light' &&
                            'h-7 rounded-md border border-line px-2 text-navy-800 hover:bg-navy-50 focus-visible:ring-brand-200',
                        props.tone === 'glass' &&
                            'h-10 gap-2 rounded-lg border border-white/25 bg-white/5 px-3 text-sm text-white backdrop-blur-sm hover:-translate-y-px hover:border-white/50 hover:bg-white/10 hover:shadow-[0_8px_20px_-10px_rgba(0,0,0,0.6)] focus-visible:ring-white/40 data-[state=open]:bg-white/15',
                    )
                "
                :aria-label="`Sayt tili: ${current.toUpperCase()}`"
            >
                <LocaleFlag v-if="tone === 'glass'" :code="current" />
                <Globe v-else class="size-3.5" />
                {{ current }}
                <ChevronDown
                    class="size-3.5 opacity-70 transition-transform group-data-[state=open]:rotate-180"
                />
            </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" :side-offset="6" class="w-40 p-1">
            <DropdownMenuItem
                v-for="locale in locales"
                :key="locale.code"
                :class="
                    cn(
                        'cursor-pointer justify-between rounded-md px-3 py-2 text-sm',
                        locale.code === current
                            ? 'bg-navy-50 font-semibold text-navy-950'
                            : 'text-navy-700',
                    )
                "
                :lang="locale.code"
                @select="select(locale.code)"
            >
                <span class="flex items-center gap-2.5">
                    <LocaleFlag
                        v-if="tone === 'glass'"
                        :code="locale.code"
                        class="size-4"
                    />
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
