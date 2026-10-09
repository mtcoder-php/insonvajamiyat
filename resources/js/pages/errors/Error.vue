<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    BookOpen,
    Clock,
    FileSearch,
    House,
    Library,
    LifeBuoy,
    Lock,
    Mail,
    RefreshCw,
    Search,
    ServerCrash,
    Wrench,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import BrandLogo from '@/components/brand/BrandLogo.vue';
import { t } from '@/lib/i18n';
import { about, contact, home } from '@/routes';
import { index as articlesIndex } from '@/routes/articles';
import { index as issuesIndex } from '@/routes/issues';

/**
 * Brendlangan xato sahifasi (App\Support\Http\ErrorPage): 403, 404, 429, 500, 503.
 * Mustaqil (layout'siz) — topilmagan manzil, admin yoki kabinet ichidagi xato uchun bir xil.
 */
const props = defineProps<{
    status: number;
    retryAfter?: number | null;
}>();

type Copy = {
    icon: Component;
    title: string;
    description: string;
};

const copy = computed<Copy>(() => {
    switch (props.status) {
        case 403:
            return {
                icon: Lock,
                title: t("Bu sahifaga kirish huquqingiz yo'q"),
                description: t(
                    "Sahifa faqat tegishli ruxsatga ega foydalanuvchilar uchun. Agar bu xato deb hisoblasangiz, tahririyat bilan bog'laning.",
                ),
            };
        case 429:
            return {
                icon: Clock,
                title: t("Juda ko'p so'rov yuborildi"),
                description: t(
                    "Xavfsizlik uchun so'rovlar soni cheklangan. Bir oz kuting va qayta urinib ko'ring.",
                ),
            };
        case 500:
            return {
                icon: ServerCrash,
                title: t('Serverda kutilmagan xatolik'),
                description: t(
                    "Xatolik haqida ma'lumot tizim administratoriga yetkazildi. Birozdan keyin qayta urinib ko'ring.",
                ),
            };
        case 503:
            return {
                icon: Wrench,
                title: t('Saytda texnik ishlar olib borilmoqda'),
                description: t(
                    'Tizim yangilanmoqda — bu odatda bir necha daqiqa davom etadi. Sahifa avtomatik yangilanadi.',
                ),
            };
        default:
            return {
                icon: FileSearch,
                title: t('Sahifa topilmadi'),
                description: t(
                    "Siz qidirgan sahifa o'chirilgan, nomi o'zgargan yoki vaqtincha mavjud emas. Manzilni tekshiring yoki qidiruvdan foydalaning.",
                ),
            };
    }
});

const links = computed(() => [
    {
        title: t('Maqolalar katalogi'),
        href: articlesIndex.url(),
        icon: BookOpen,
    },
    { title: t('Jurnal sonlari'), href: issuesIndex.url(), icon: Library },
    { title: t('Jurnal haqida'), href: about.url(), icon: LifeBuoy },
    { title: t('Aloqa'), href: contact.url(), icon: Mail },
]);

const query = ref('');

function search(): void {
    const q = query.value.trim();

    router.visit(articlesIndex.url(q ? { query: { q } } : undefined));
}

function goBack(): void {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        router.visit(home.url());
    }
}

// 429 / 503 — orqaga sanoq va avtomatik yangilash
const countdown = ref<number | null>(null);
let timer: ReturnType<typeof setInterval> | undefined;

onMounted(() => {
    if (props.status !== 429 && props.status !== 503) {
        return;
    }

    countdown.value = Math.min(
        props.retryAfter ?? (props.status === 503 ? 30 : 60),
        300,
    );
    timer = setInterval(() => {
        if (countdown.value === null) {
            return;
        }

        countdown.value -= 1;

        if (countdown.value <= 0) {
            clearInterval(timer);

            if (props.status === 503) {
                window.location.reload();
            }
        }
    }, 1000);
});

onBeforeUnmount(() => clearInterval(timer));
</script>

<template>
    <Head :title="copy.title">
        <meta name="robots" content="noindex, nofollow" />
    </Head>

    <div
        class="relative isolate flex min-h-svh flex-col overflow-hidden bg-[#f6f8fb] text-navy-950"
    >
        <!-- Fon: girih naqshi va yumshoq yorug'lik -->
        <div
            class="absolute inset-0 -z-10 bg-girih opacity-[0.05]"
            aria-hidden="true"
        />
        <div
            class="absolute -top-40 left-1/2 -z-10 size-[640px] -translate-x-1/2 rounded-full bg-brand-200/40 blur-3xl"
            aria-hidden="true"
        />

        <header
            class="mx-auto flex w-full max-w-6xl items-center justify-between px-5 py-6 sm:px-8"
        >
            <Link
                :href="home()"
                class="transition-opacity duration-200 hover:opacity-80"
            >
                <BrandLogo size="sm" />
            </Link>
            <Link
                :href="contact()"
                class="hidden items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-navy-600 transition-colors hover:bg-white hover:text-brand-700 sm:inline-flex"
            >
                <Mail class="size-4" />
                {{ t('Tahririyat bilan aloqa') }}
            </Link>
        </header>

        <main
            class="mx-auto flex w-full max-w-3xl flex-1 flex-col items-center justify-center px-5 pb-16 text-center sm:px-8"
        >
            <span
                class="group relative mb-6 flex size-20 items-center justify-center rounded-3xl bg-navy-950 text-gold-300 shadow-[0_24px_48px_-24px_rgba(0,30,60,0.9)]"
            >
                <component
                    :is="copy.icon"
                    class="size-9 transition-transform duration-500 group-hover:rotate-[-8deg]"
                />
                <span
                    class="absolute -right-2 -bottom-2 rounded-full bg-gold-500 px-2 py-0.5 font-mono text-xs font-bold text-navy-950 tabular-nums shadow"
                    >{{ status }}</span
                >
            </span>

            <p
                class="bg-gradient-to-br from-navy-900 via-brand-700 to-gold-500 bg-clip-text font-serif text-[88px] leading-none font-bold text-transparent tabular-nums select-none sm:text-[120px]"
                aria-hidden="true"
            >
                {{ status }}
            </p>

            <h1 class="mt-4 font-serif text-2xl font-bold sm:text-3xl">
                {{ copy.title }}
            </h1>
            <p class="mt-3 max-w-xl text-[15px] leading-relaxed text-navy-600">
                {{ copy.description }}
            </p>

            <p
                v-if="countdown !== null && countdown > 0"
                class="mt-4 inline-flex items-center gap-2 rounded-full bg-white px-4 py-1.5 text-sm font-medium text-navy-700 tabular-nums ring-1 ring-line"
            >
                <RefreshCw class="size-4 animate-spin text-brand-600" />
                {{
                    status === 503
                        ? t(':seconds soniyadan keyin yangilanadi', {
                              seconds: countdown,
                          })
                        : t(':seconds soniyadan keyin qayta urinish mumkin', {
                              seconds: countdown,
                          })
                }}
            </p>

            <!-- 404: qidiruv -->
            <form
                v-if="status === 404"
                class="mt-8 flex w-full max-w-lg items-center gap-2 rounded-xl bg-white p-1.5 shadow-[0_18px_40px_-28px_rgba(0,36,66,0.55)] ring-1 ring-line transition-shadow focus-within:ring-2 focus-within:ring-brand-300"
                role="search"
                @submit.prevent="search"
            >
                <Search class="ml-2.5 size-5 shrink-0 text-navy-400" />
                <label for="error-search" class="sr-only">{{
                    t('Maqola qidirish')
                }}</label>
                <input
                    id="error-search"
                    v-model="query"
                    type="search"
                    maxlength="100"
                    :placeholder="t('Maqola nomi, muallif yoki kalit so\'z...')"
                    class="h-10 min-w-0 flex-1 bg-transparent text-sm text-navy-900 outline-none placeholder:text-navy-400"
                />
                <button
                    type="submit"
                    class="inline-flex h-10 shrink-0 items-center rounded-lg bg-brand-600 px-4 text-sm font-semibold text-white transition-colors hover:bg-brand-500"
                >
                    {{ t('Qidirish') }}
                </button>
            </form>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <Link
                    :href="home()"
                    class="group inline-flex h-11 items-center gap-2 rounded-lg bg-navy-950 px-5 text-sm font-semibold text-white shadow-[0_12px_26px_-14px_rgba(0,30,60,0.9)] transition-all duration-200 hover:-translate-y-0.5 hover:bg-navy-800"
                >
                    <House
                        class="size-4 text-gold-300 transition-transform duration-300 group-hover:scale-110"
                    />
                    {{ t('Bosh sahifaga qaytish') }}
                </Link>
                <button
                    v-if="status !== 503"
                    type="button"
                    class="group inline-flex h-11 items-center gap-2 rounded-lg bg-white px-5 text-sm font-semibold text-navy-800 ring-1 ring-line transition-all duration-200 hover:-translate-y-0.5 hover:text-brand-700 hover:ring-brand-200"
                    @click="goBack"
                >
                    <ArrowLeft
                        class="size-4 transition-transform duration-300 group-hover:-translate-x-0.5"
                    />
                    {{ t('Orqaga') }}
                </button>
            </div>

            <!-- Foydali havolalar -->
            <nav
                v-if="status === 404 || status === 403"
                class="mt-12 grid w-full grid-cols-2 gap-3 sm:grid-cols-4"
                :aria-label="t('Foydali havolalar')"
            >
                <Link
                    v-for="link in links"
                    :key="link.href"
                    :href="link.href"
                    class="group flex flex-col items-center gap-2 rounded-xl border border-line bg-white px-3 py-4 text-sm font-medium text-navy-700 transition-all duration-300 hover:-translate-y-0.5 hover:border-brand-200 hover:text-brand-700 hover:shadow-[0_16px_34px_-22px_rgba(0,36,66,0.45)]"
                >
                    <span
                        class="flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600 transition-all duration-300 group-hover:-rotate-6 group-hover:bg-navy-950 group-hover:text-gold-300"
                    >
                        <component :is="link.icon" class="size-5" />
                    </span>
                    {{ link.title }}
                </Link>
            </nav>
        </main>

        <footer class="pb-6 text-center text-xs text-navy-400">
            © {{ new Date().getFullYear() }} «Inson va Jamiyat»
        </footer>
    </div>
</template>
