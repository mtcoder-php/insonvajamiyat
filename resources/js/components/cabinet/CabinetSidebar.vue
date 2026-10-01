<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronDown, Quote } from '@lucide/vue';
import { computed, ref } from 'vue';
import UserAvatar from '@/components/users/UserAvatar.vue';
import { useCabinetNav } from '@/composables/useCabinetNav';
import { cn } from '@/lib/utils';
import { cabinetUsefulLinks } from '@/navigation/cabinet';
import { edit as profileEdit } from '@/routes/profile';

/**
 * Muallif kabineti chap paneli (dizayn: "Muallif kabineti"):
 * profil kartasi, menyu, foydali havolalar va iqtibos kartasi.
 */
const page = usePage();

const user = computed(() => page.props.auth.user);
const position = computed(() =>
    typeof user.value?.position === 'string' && user.value.position !== ''
        ? user.value.position
        : 'Muallif',
);
const unread = computed(() => page.props.notifications?.unread ?? 0);

const { items, isActive } = useCabinetNav();
const usefulLinks = cabinetUsefulLinks();
const usefulOpen = ref(true);

const quoteImage = '/images/admin/sidebar-banner.png';
</script>

<template>
    <aside class="flex flex-col gap-4" aria-label="Muallif kabineti menyusi">
        <!-- Profil kartasi -->
        <Link
            v-if="user"
            :href="profileEdit()"
            class="group relative isolate flex items-center gap-3 overflow-hidden rounded-xl bg-navy-950 p-4 text-white shadow-[0_14px_30px_-20px_rgba(0,30,60,0.9)] transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_18px_36px_-18px_rgba(0,30,60,0.9)]"
        >
            <span
                class="absolute inset-0 -z-10 bg-girih opacity-[0.06]"
                aria-hidden="true"
            />
            <span
                class="absolute -top-16 -right-10 -z-10 size-40 rounded-full bg-brand-500/30 blur-2xl transition-opacity duration-500 group-hover:opacity-80"
                aria-hidden="true"
            />
            <UserAvatar
                :name="user.name"
                :url="user.avatar"
                size="lg"
                class="ring-2 ring-white/20 transition-transform duration-300 group-hover:scale-105"
            />
            <span class="min-w-0">
                <span class="block truncate font-sans text-[15px] font-bold">
                    {{ user.name }}
                </span>
                <span class="block truncate text-xs text-white/70">
                    {{ position }}
                </span>
                <span class="mt-0.5 block truncate text-xs text-white/60">
                    {{ user.email }}
                </span>
            </span>
        </Link>

        <nav
            class="rounded-xl border border-line bg-white p-2 shadow-[0_1px_2px_rgba(0,30,60,0.05)]"
        >
            <ul class="flex flex-col gap-0.5">
                <li v-for="item in items" :key="item.title">
                    <span
                        v-if="item.disabled"
                        class="flex cursor-not-allowed items-center gap-3 rounded-lg px-3 py-2.5 text-[13px] font-medium text-navy-400"
                        title="Tez orada"
                    >
                        <component :is="item.icon" class="size-[18px]" />
                        <span class="flex-1">{{ item.title }}</span>
                        <span
                            v-if="item.badge && unread > 0"
                            class="flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-bold text-white tabular-nums"
                        >
                            {{ unread > 99 ? '99+' : unread }}
                        </span>
                        <span
                            v-else
                            class="rounded bg-navy-50 px-1.5 py-0.5 text-[10px] text-navy-400"
                        >
                            tez orada
                        </span>
                    </span>
                    <Link
                        v-else
                        :href="item.href"
                        :aria-current="isActive(item) ? 'page' : undefined"
                        :class="
                            cn(
                                'group/item relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-[13px] font-medium transition-all duration-200',
                                isActive(item)
                                    ? 'bg-brand-600 text-white shadow-[0_8px_18px_-10px_rgba(0,108,246,0.9)]'
                                    : 'text-navy-700 hover:translate-x-0.5 hover:bg-brand-50 hover:text-brand-700',
                            )
                        "
                    >
                        <component
                            :is="item.icon"
                            :class="
                                cn(
                                    'size-[18px] transition-transform duration-200 group-hover/item:scale-110',
                                    isActive(item)
                                        ? 'text-white'
                                        : 'text-navy-500 group-hover/item:text-brand-600',
                                )
                            "
                        />
                        <span class="flex-1">{{ item.title }}</span>
                    </Link>
                </li>
            </ul>

            <div class="mt-2 border-t border-line pt-2">
                <button
                    type="button"
                    class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-[13px] font-bold text-navy-950 transition-colors hover:bg-navy-50"
                    :aria-expanded="usefulOpen"
                    @click="usefulOpen = !usefulOpen"
                >
                    Foydali havolalar
                    <ChevronDown
                        :class="
                            cn(
                                'size-4 text-navy-500 transition-transform duration-300',
                                usefulOpen && 'rotate-180',
                            )
                        "
                    />
                </button>
                <ul
                    v-show="usefulOpen"
                    class="mt-0.5 flex flex-col gap-0.5 pb-1"
                >
                    <li v-for="link in usefulLinks" :key="link.title">
                        <Link
                            :href="link.href"
                            class="group/item flex items-center gap-3 rounded-lg px-3 py-2 text-[12.5px] text-navy-600 transition-all duration-200 hover:translate-x-0.5 hover:bg-brand-50 hover:text-brand-700"
                        >
                            <component
                                :is="link.icon"
                                class="size-4 shrink-0 text-navy-400 transition-colors group-hover/item:text-brand-600"
                            />
                            {{ link.title }}
                        </Link>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Iqtibos kartasi -->
        <figure
            class="group relative isolate hidden min-h-64 overflow-hidden rounded-xl bg-navy-950 p-5 text-white shadow-[0_14px_30px_-20px_rgba(0,30,60,0.9)] lg:flex lg:flex-col"
        >
            <div
                class="absolute inset-0 -z-10 bg-cover bg-center transition-transform duration-[1500ms] ease-out group-hover:scale-105"
                :style="{ backgroundImage: `url('${quoteImage}')` }"
                aria-hidden="true"
            />
            <div
                class="absolute inset-0 -z-10 bg-gradient-to-b from-navy-950/70 via-navy-950/30 to-navy-950/80"
                aria-hidden="true"
            />
            <Quote class="size-6 text-gold-300" />
            <blockquote
                class="mt-3 font-serif text-lg leading-snug text-white/95 italic"
            >
                “Ilm — insonni yuksaltiradi, jamiyatni rivojlantiradi.”
            </blockquote>
        </figure>
    </aside>
</template>
