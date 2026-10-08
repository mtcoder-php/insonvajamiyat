<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ActiveUsersCard from '@/components/admin/dashboard/ActiveUsersCard.vue';
import AiUsageCard from '@/components/admin/dashboard/AiUsageCard.vue';
import DynamicsChart from '@/components/admin/dashboard/DynamicsChart.vue';
import LatestSubmissionsTable from '@/components/admin/dashboard/LatestSubmissionsTable.vue';
import NotificationsCard from '@/components/admin/dashboard/NotificationsCard.vue';
import PaymentsChart from '@/components/admin/dashboard/PaymentsChart.vue';
import QuickActionsCard from '@/components/admin/dashboard/QuickActionsCard.vue';
import RecentPaymentsCard from '@/components/admin/dashboard/RecentPaymentsCard.vue';
import StatCard from '@/components/admin/dashboard/StatCard.vue';
import StatusDonut from '@/components/admin/dashboard/StatusDonut.vue';
import SystemHealthCard from '@/components/admin/dashboard/SystemHealthCard.vue';
import WelcomeBanner from '@/components/admin/dashboard/WelcomeBanner.vue';
import { dashboard } from '@/routes/admin';
import type { AdminDashboardProps } from '@/types';
import { t, tk } from '@/lib/i18n';

/**
 * Super admin dashboard (super admin dashboard.png):
 * chapda asosiy kontent, o'ngda bildirishnomalar / faol foydalanuvchilar /
 * tizim holati / tezkor amallar.
 */
defineProps<AdminDashboardProps>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: tk('Admin panel'), href: dashboard() }],
    },
});
</script>

<template>
    <Head :title="t('Dashboard')" />

    <div
        class="grid flex-1 gap-5 bg-[#f5f7fb] p-4 md:p-6 2xl:grid-cols-[minmax(0,1fr)_22rem]"
    >
        <!-- Asosiy kontent -->
        <div class="flex min-w-0 flex-col gap-5">
            <WelcomeBanner />

            <section
                class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3 xl:grid-cols-5"
                :aria-label="t('Maqolalar statistikasi')"
            >
                <StatCard v-for="card in cards" :key="card.key" :card="card" />
            </section>

            <div
                class="grid gap-5 xl:grid-cols-[minmax(0,1.7fr)_minmax(0,1fr)]"
            >
                <DynamicsChart :data="dynamics" />
                <StatusDonut :data="statusBreakdown" />
            </div>

            <LatestSubmissionsTable :items="latestSubmissions" />

            <div
                class="grid gap-5 lg:grid-cols-2 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_minmax(0,1fr)]"
            >
                <PaymentsChart
                    :data="payments"
                    class="lg:col-span-2 xl:col-span-1"
                />
                <RecentPaymentsCard :items="recentPayments" />
                <AiUsageCard :data="aiUsage" />
            </div>
        </div>

        <!-- O'ng panel -->
        <aside
            class="grid content-start gap-5 md:grid-cols-2 2xl:grid-cols-1"
            :aria-label="t('Qo\'shimcha ma\'lumotlar')"
        >
            <NotificationsCard :items="notificationsList" />
            <ActiveUsersCard :items="activeUsers" />
            <SystemHealthCard :items="systemHealth" />
            <QuickActionsCard />
        </aside>
    </div>
</template>
