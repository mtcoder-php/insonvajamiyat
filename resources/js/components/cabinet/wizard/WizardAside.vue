<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { BookOpenText, Download, Lightbulb, Save, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import DashCard from '@/components/admin/dashboard/DashCard.vue';
import ActionDialog from '@/components/admin/ui/ActionDialog.vue';
import { formatDate, formatTime } from '@/lib/format';
import type { ArticleDraft, CabinetLinks } from '@/types';

/**
 * Forma yon paneli: joriy bosqich bo'yicha maslahatlar, foydali havolalar,
 * qoralama holati va qoralamani o'chirish.
 */
const props = defineProps<{
    step: number;
    article: ArticleDraft | null;
    links: CabinetLinks;
}>();

const tips: Record<number, string[]> = {
    1: [
        "Sarlavha qisqa va aniq bo'lsin — odatda 10–15 so'z.",
        "Maqola turini hajmi va ko'rib chiqish muddatiga qarab tanlang.",
        "Maqola tili — matn yozilgan til; annotatsiya va kalit so'zlar shu tilda majburiy.",
    ],
    2: [
        "Mualliflarni maqolada qanday tartibda bo'lsa, shunday kiriting.",
        "Aloqa uchun mas'ul muallif tahririyat bilan yozishmalarni olib boradi.",
        "Hammuallif tizimda ro'yxatdan o'tgan bo'lsa, maqola uning kabinetida ham ko'rinadi.",
    ],
    3: [
        "Annotatsiya 150–300 so'z: dolzarblik, maqsad, usullar, natijalar va xulosa.",
        'Annotatsiyada qisqartmalar, formulalar va iqtiboslardan foydalanmang.',
        'Ingliz tilidagi annotatsiya maqolaning xalqaro bazalarda topilishini osonlashtiradi.',
    ],
    4: [
        "3–10 ta kalit so'z yoki so'z birikmasi kiriting.",
        "Sarlavhadagi so'zlarni aynan takrorlamang — mavzuni kengroq yoriting.",
        "Bir nechta so'zni vergul bilan ajratib, birdaniga qo'yishingiz mumkin.",
    ],
    5: [
        'Maqolani jurnal shabloni asosida rasmiylashtiring.',
        "Mustaqil taqriz uchun faylda mualliflar ismi ko'rsatilmasligi tavsiya etiladi.",
        "Rasmlar kamida 300 dpi sifatda bo'lsin.",
    ],
    6: [
        "Barcha bosqichlarni diqqat bilan tekshiring — yuborilgandan keyin ma'lumotlarni o'zgartirib bo'lmaydi.",
        'Kamchilik bo\'lsa, "Tahrirlash" orqali tegishli bosqichga qayting.',
    ],
    7: [
        'Yuborilgan maqola holatini "Mening maqolalarim" bo\'limida kuzatasiz.',
        'Tahririyat izohlari va taqriz natijalari kabinetingizga keladi.',
    ],
};

const stepTips = computed(() => tips[props.step] ?? []);

const deleteOpen = ref(false);
const deleting = ref(false);

function destroy(): void {
    if (!props.article) {
        return;
    }

    deleting.value = true;
    router.delete(props.article.urls.destroy, {
        onFinish: () => (deleting.value = false),
    });
}
</script>

<template>
    <aside class="grid content-start gap-5 md:grid-cols-2 xl:grid-cols-1">
        <DashCard>
            <h2
                class="mb-3 flex items-center gap-2 font-sans text-[15px] font-bold text-navy-950"
            >
                <Lightbulb class="size-[18px] text-gold-500" />
                Maslahatlar
            </h2>
            <ul class="grid gap-2.5">
                <li
                    v-for="tip in stepTips"
                    :key="tip"
                    class="flex gap-2 text-[13px] leading-relaxed text-navy-700"
                >
                    <span
                        class="mt-2 size-1.5 shrink-0 rounded-full bg-brand-500"
                        aria-hidden="true"
                    />
                    {{ tip }}
                </li>
            </ul>
            <div class="mt-4 grid gap-2 border-t border-line pt-4">
                <a
                    v-if="links.template"
                    :href="links.template"
                    download
                    class="group flex items-center gap-2.5 rounded-lg px-2 py-1.5 text-[13px] font-medium text-navy-700 transition-colors hover:bg-brand-50 hover:text-brand-700"
                >
                    <Download
                        class="size-4 text-navy-400 transition-transform group-hover:translate-y-0.5 group-hover:text-brand-600"
                    />
                    Maqola shablonini yuklab olish
                </a>
                <Link
                    :href="links.guidelines"
                    class="group flex items-center gap-2.5 rounded-lg px-2 py-1.5 text-[13px] font-medium text-navy-700 transition-colors hover:bg-brand-50 hover:text-brand-700"
                >
                    <BookOpenText
                        class="size-4 text-navy-400 group-hover:text-brand-600"
                    />
                    Mualliflar uchun yo'riqnoma
                </Link>
            </div>
        </DashCard>

        <DashCard v-if="article">
            <h2
                class="mb-2 flex items-center gap-2 font-sans text-[15px] font-bold text-navy-950"
            >
                <Save class="size-[18px] text-brand-600" />
                Qoralama
            </h2>
            <p class="text-[13px] leading-relaxed text-navy-600">
                Ma'lumotlar har bir bosqichda saqlanadi — formani istalgan
                vaqtda "Mening maqolalarim" bo'limidan davom ettirishingiz
                mumkin.
            </p>
            <p
                v-if="article.updatedAt"
                class="mt-2 text-xs text-navy-400 tabular-nums"
            >
                Oxirgi saqlangan: {{ formatDate(article.updatedAt) }}
                {{ formatTime(article.updatedAt) }}
            </p>
            <button
                type="button"
                class="group mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg border border-red-200 px-3 py-2 text-[13px] font-medium text-red-700 transition-colors hover:bg-red-50"
                @click="deleteOpen = true"
            >
                <Trash2
                    class="size-4 transition-transform group-hover:-rotate-12"
                />
                Qoralamani o'chirish
            </button>
        </DashCard>

        <ActionDialog
            v-model:open="deleteOpen"
            title="Qoralamani o'chirasizmi?"
            description="Kiritilgan barcha ma'lumotlar va yuklangan fayllar butunlay o'chiriladi. Bu amalni bekor qilib bo'lmaydi."
            :icon="Trash2"
            tone="danger"
            confirm-text="O'chirish"
            :processing="deleting"
            @confirm="destroy"
        />
    </aside>
</template>
