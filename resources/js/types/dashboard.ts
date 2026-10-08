/**
 * Admin dashboard ma'lumotlari — App\Services\Admin\DashboardService bilan mos.
 */
export type StatusGroupKey =
    | 'new'
    | 'reviewing'
    | 'revision'
    | 'accepted'
    | 'published';

export type StatCardData = {
    key: 'total' | 'reviewing' | 'revision' | 'accepted' | 'published';
    value: number;
    trend: number | null;
    period: 'month' | 'week';
};

export type MonthlyDynamics = {
    year: number;
    submitted: number[];
    accepted: number[];
    published: number[];
};

export type StatusBreakdown = {
    total: number;
    groups: { key: StatusGroupKey; value: number }[];
};

export type LatestSubmission = {
    id: number;
    title: string;
    author: string;
    issue: string | null;
    submittedAt: string | null;
    status: string;
    statusGroup: StatusGroupKey | 'other';
    statusLabel: string;
    coverUrl: string | null;
    /** Maqolalar bo'limida ochish (ruxsat bo'lmasa null) */
    url: string | null;
};

export type PaymentsMonthly = {
    year: number;
    click: number[];
    payme: number[];
    /** Qo'lda tasdiqlangan (bank o'tkazmasi) */
    manual: number[];
    total: number;
};

export type RecentPayment = {
    id: number;
    provider: 'click' | 'payme' | 'manual';
    amount: number;
    date: string | null;
    status: string;
    statusLabel: string;
};

export type AiUsage = {
    total: number;
    trend: number | null;
    types: { key: string; label: string; value: number }[];
};

export type DashboardNotification = {
    id: string;
    kind: string;
    title: string;
    message: string | null;
    articleTitle?: string | null;
    openUrl?: string;
    read: boolean;
    createdAt: string | null;
};

export type ActiveUser = {
    id: number;
    name: string;
    role: string | null;
    avatarUrl: string | null;
    lastSeenAt: string | null;
};

export type SystemHealthItem = {
    key: string;
    label: string;
    state: 'up' | 'down' | 'not_configured';
};

export type AdminDashboardProps = {
    cards: StatCardData[];
    dynamics: MonthlyDynamics;
    statusBreakdown: StatusBreakdown;
    latestSubmissions: LatestSubmission[];
    payments: PaymentsMonthly;
    recentPayments: RecentPayment[];
    aiUsage: AiUsage;
    notificationsList: DashboardNotification[];
    activeUsers: ActiveUser[];
    systemHealth: SystemHealthItem[];
};
