<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/composables/usePermissions';
import { about, register } from '@/routes';
import { index as articlesIndex } from '@/routes/articles';

const { auth } = usePermissions();
</script>

<template>
    <Head title="Bosh sahifa" />

    <section class="border-b">
        <div
            class="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-16 sm:px-6 lg:px-8"
        >
            <span
                class="w-fit rounded-full border px-3 py-1 text-xs font-medium text-muted-foreground"
            >
                Ilmiy jurnal
            </span>
            <h1 class="max-w-3xl text-4xl font-bold tracking-tight sm:text-5xl">
                Insonni anglash — jamiyatni anglashdir.
            </h1>
            <p class="max-w-2xl text-lg text-muted-foreground">
                "Inson va Jamiyat" ilmiy jurnali — tarix, etnologiya,
                antropologiya va falsafaga doir ilmiy tadqiqotlarni nashr
                etuvchi ilmiy nashr.
            </p>
            <div class="flex flex-wrap gap-3">
                <Button as-child>
                    <Link :href="articlesIndex()">Maqolalar katalogi</Link>
                </Button>
                <Button as-child variant="outline">
                    <Link :href="about()">Jurnal haqida</Link>
                </Button>
                <Button v-if="!auth.user" as-child variant="ghost">
                    <Link :href="register()"
                        >Maqola yuborish uchun ro'yxatdan o'tish</Link
                    >
                </Button>
            </div>
        </div>
    </section>
</template>
