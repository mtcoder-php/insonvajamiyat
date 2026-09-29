<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes/cabinet';
import { edit as profileEdit } from '@/routes/profile';

type Profile = {
    last_name: string;
    first_name: string;
    middle_name: string | null;
    organization: string | null;
    position: string | null;
    academic_degree: string | null;
    orcid: string | null;
};

defineProps<{
    profile: Profile | null;
    profileCompleted: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Muallif kabineti', href: dashboard() }],
    },
});
</script>

<template>
    <Head title="Muallif kabineti" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">
                Xush kelibsiz, {{ profile?.first_name ?? 'muallif' }}!
            </h1>
            <p class="text-muted-foreground">
                Maqolalaringizni boshqaring va nashr jarayonini kuzating.
            </p>
        </div>

        <Card v-if="!profileCompleted">
            <CardHeader>
                <CardTitle>Profilingizni to'ldiring</CardTitle>
                <CardDescription>
                    Maqola yuborishdan oldin tashkilot, lavozim, ilmiy daraja va
                    ORCID ma'lumotlarini kiriting.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Button as-child>
                    <Link :href="profileEdit()">Profilga o'tish</Link>
                </Button>
            </CardContent>
        </Card>

        <div class="grid gap-4 md:grid-cols-3">
            <Card>
                <CardHeader>
                    <CardDescription>Jami maqolalar</CardDescription>
                    <CardTitle class="text-3xl">0</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader>
                    <CardDescription>Ko'rib chiqilmoqda</CardDescription>
                    <CardTitle class="text-3xl">0</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader>
                    <CardDescription>Nashr etilgan</CardDescription>
                    <CardTitle class="text-3xl">0</CardTitle>
                </CardHeader>
            </Card>
        </div>
    </div>
</template>
