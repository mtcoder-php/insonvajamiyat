<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { usePermissions } from '@/composables/usePermissions';
import { roleLabels } from '@/lib/roles';
import { dashboard } from '@/routes/admin';

defineProps<{
    stats: {
        authors: number;
        staff: number;
        blocked: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Admin panel', href: dashboard() }],
    },
});

const { auth } = usePermissions();
</script>

<template>
    <Head title="Admin panel" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">
                Assalomu alaykum, {{ auth.user?.name }}
            </h1>
            <p class="text-muted-foreground">
                {{ auth.roles.map((r) => roleLabels[r]).join(', ') }}
            </p>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <Card>
                <CardHeader>
                    <CardDescription>Mualliflar</CardDescription>
                    <CardTitle class="text-3xl">{{ stats.authors }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader>
                    <CardDescription>Xodimlar</CardDescription>
                    <CardTitle class="text-3xl">{{ stats.staff }}</CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader>
                    <CardDescription>Bloklanganlar</CardDescription>
                    <CardTitle class="text-3xl">{{ stats.blocked }}</CardTitle>
                </CardHeader>
            </Card>
        </div>
    </div>
</template>
