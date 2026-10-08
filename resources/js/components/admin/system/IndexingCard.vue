<script setup lang="ts">
import { Check, Copy, DatabaseZap, ExternalLink } from '@lucide/vue';
import { ref } from 'vue';
import SectionCard from '@/components/admin/ui/SectionCard.vue';
import { t } from '@/lib/i18n';
import { oai } from '@/routes';

/**
 * Indekslash: OAI-PMH manzili (BASE, OpenAIRE, CyberLeninka va boshqa bazalarga
 * ro'yxatdan o'tishda shu manzil beriladi) va Crossref haqida eslatma.
 */
const baseUrl = new URL(oai().url, window.location.origin).toString();
const copied = ref(false);

async function copy(): Promise<void> {
    try {
        await navigator.clipboard.writeText(baseUrl);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    } catch {
        copied.value = false;
    }
}
</script>

<template>
    <SectionCard
        :title="t('Indekslash (OAI-PMH)')"
        :description="
            t(
                'Ilmiy bazalar nashr etilgan maqolalarni shu manzil orqali avtomatik yig\'adi',
            )
        "
        :icon="DatabaseZap"
    >
        <div class="grid gap-4">
            <div
                class="flex flex-wrap items-center gap-2 rounded-lg border border-line bg-navy-50/40 px-3 py-2.5"
            >
                <code
                    class="min-w-0 flex-1 truncate font-mono text-[13px] text-navy-900"
                    >{{ baseUrl }}</code
                >
                <button
                    type="button"
                    class="inline-flex h-8 items-center gap-1.5 rounded-md border border-line bg-white px-2.5 text-xs font-semibold text-navy-700 transition-all hover:-translate-y-px hover:border-brand-300 hover:text-brand-700"
                    @click="copy"
                >
                    <Check v-if="copied" class="size-3.5 text-emerald-600" />
                    <Copy v-else class="size-3.5" />
                    <span aria-live="polite">{{
                        copied ? t('Nusxalandi') : t('Nusxalash')
                    }}</span>
                </button>
                <a
                    :href="`${baseUrl}?verb=Identify`"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex h-8 items-center gap-1.5 rounded-md border border-line bg-white px-2.5 text-xs font-semibold text-navy-700 transition-all hover:-translate-y-px hover:border-brand-300 hover:text-brand-700"
                >
                    <ExternalLink class="size-3.5" />
                    {{ t('Tekshirish') }}
                </a>
            </div>
            <ul class="grid gap-1.5 text-[13px] leading-relaxed text-navy-600">
                <li>
                    {{
                        t(
                            "Format: Dublin Core (oai_dc). To'plamlar: ilmiy yo'nalishlar va jurnal sonlari.",
                        )
                    }}
                </li>
                <li>
                    {{
                        t(
                            "Ro'yxatdan o'tish: BASE (base-search.net), OpenAIRE, CyberLeninka — «OAI-PMH base URL» maydoniga yuqoridagi manzilni kiriting.",
                        )
                    }}
                </li>
                <li>
                    {{
                        t(
                            'Crossref: chop etilgan son sahifasida «Crossref XML» tugmasi — DOI deposit faylini yuklab olib, Crossref kabinetiga yuklang.',
                        )
                    }}
                </li>
            </ul>
        </div>
    </SectionCard>
</template>
