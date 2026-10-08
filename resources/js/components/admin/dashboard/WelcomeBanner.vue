<script setup lang="ts">
import { CalendarDays } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { usePermissions } from '@/composables/usePermissions';
import { formatDate } from '@/lib/format';
import { primaryRoleLabel } from '@/lib/roles';
import { t } from '@/lib/i18n';

/**
 * "Assalomu alaykum" banneri (super admin dashboard.png).
 * Fon: public/images/admin/banner.png; o'ngda jonli sana va soat.
 */
const { auth } = usePermissions();

const name = computed(() => auth.value.user?.name ?? '');
const role = computed(() => primaryRoleLabel(auth.value.roles));

const now = ref(new Date());
let timer: ReturnType<typeof setInterval> | undefined;

const time = computed(
    () =>
        `${String(now.value.getHours()).padStart(2, '0')}:${String(now.value.getMinutes()).padStart(2, '0')}`,
);

onMounted(() => {
    timer = setInterval(() => (now.value = new Date()), 30_000);
});
onBeforeUnmount(() => clearInterval(timer));

const bannerUrl = '/images/admin/banner.png';
</script>

<template>
    <section
        class="group relative isolate overflow-hidden rounded-xl bg-navy-950 text-white shadow-[0_12px_32px_-18px_rgba(0,30,60,0.6)]"
    >
        <div
            class="absolute inset-0 -z-10 bg-cover bg-[position:right_center] bg-no-repeat transition-transform duration-[1500ms] ease-out group-hover:scale-[1.03]"
            :style="{ backgroundImage: `url('${bannerUrl}')` }"
            aria-hidden="true"
        />
        <div
            class="absolute inset-0 -z-10 bg-gradient-to-r from-navy-950 via-navy-950/85 to-navy-950/10"
            aria-hidden="true"
        />

        <div
            class="flex flex-col gap-6 p-6 sm:flex-row sm:items-start sm:justify-between lg:p-7"
        >
            <div class="max-w-md">
                <p class="text-sm text-white/80">
                    {{ t('Assalomu alaykum,') }}
                </p>
                <h1
                    class="mt-1 font-sans text-2xl font-bold text-white sm:text-[1.75rem]"
                >
                    {{ name }}
                </h1>
                <p class="mt-1 text-sm font-medium text-white/85">{{ role }}</p>
                <p class="mt-5 text-sm leading-relaxed text-white/75">
                    {{
                        t(
                            "Tizim orqali jurnal faoliyati, maqolalar, to'lovlar va foydalanuvchilar bilan bog'liq barcha jarayonlarni boshqarishingiz mumkin.",
                        )
                    }}
                </p>
            </div>

            <div class="flex shrink-0 flex-col items-start gap-6 sm:items-end">
                <div
                    class="flex items-start gap-3 rounded-lg bg-navy-950/40 px-3 py-2 backdrop-blur-sm"
                >
                    <CalendarDays class="mt-0.5 size-6 text-white/85" />
                    <div class="leading-tight">
                        <p class="text-[11px] text-white/65">
                            {{ t('Bugun') }}
                        </p>
                        <p class="text-sm font-semibold">
                            {{ formatDate(now) }}
                        </p>
                        <p class="text-sm font-semibold tabular-nums">
                            {{ time }}
                        </p>
                    </div>
                </div>
                <blockquote
                    class="hidden max-w-60 text-right font-serif text-sm leading-snug text-white/90 italic sm:block"
                >
                    {{
                        t(
                            '“Ilm — insonni yuksaltiradi, jamiyatni rivojlantiradi.”',
                        )
                    }}
                </blockquote>
            </div>
        </div>
    </section>
</template>
