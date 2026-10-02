/**
 * Admin → Statistika va hisobotlar (App\Services\Reports\*) va Audit log.
 */

/** Grafik seriyasi (LineChart, BarChart) */
export type ChartSeries = {
    key: string;
    label: string;
    color: string;
    values: number[];
};

export type ReportTab = 'overview' | 'reviewers' | 'exports';

export type ReportTableTab =
    | 'all'
    | 'new'
    | 'reviewing'
    | 'accepted'
    | 'published'
    | 'rejected';

export type ReportFilters = {
    from: string;
    to: string;
    days: number;
    daily: boolean;
    label: string;
    subject: string | null;
    tab: ReportTab;
    status: ReportTableTab;
    q: string | null;
};

export type ReportOption = { value: string; label: string };

export type ReportKpiKey =
    | 'submitted'
    | 'accepted'
    | 'rejected'
    | 'review_days'
    | 'authors'
    | 'views';

export type ReportKpi = {
    key: ReportKpiKey;
    value: number | null;
    previous: number | null;
    trend: number | null;
    better: 'up' | 'down';
};

export type ReportDynamics = {
    labels: string[];
    submitted: number[];
    accepted: number[];
    rejected: number[];
};

export type ReportShareItem = { key: string; label: string; value: number };

export type ReportShare = { total: number; items: ReportShareItem[] };

export type ReportOrganizations = ReportShare & {
    top: { name: string; authors: number }[];
};

export type ReportRevenue = {
    labels: string[];
    click: number[];
    payme: number[];
    manual: number[];
    total: number;
    previous: number;
    trend: number | null;
    count: number;
};

export type ReportAi = {
    labels: string[];
    series: number[];
    metrics: {
        key: 'total' | 'completed' | 'failed' | 'cost';
        value: number;
        trend: number | null;
    }[];
    tokens: number;
};

export type ReportQuickItem = {
    key: 'submitted' | 'reviews' | 'payments' | 'ai';
    value: number;
    trend: number | null;
    hint: string | null;
};

export type ReportSystem = {
    items: {
        key: 'users' | 'new_users' | 'online' | 'issues' | 'archive' | 'tokens';
        value: number;
        trend: number | null;
    }[];
    registrations: number[];
};

export type ReportTopAuthor = {
    name: string;
    organization: string | null;
    articles: number;
};

export type ReportArticleRow = {
    id: number;
    code: string;
    title: string;
    author: string | null;
    authorsCount: number;
    subject: string | null;
    status: string;
    statusGroup: string;
    statusLabel: string;
    submittedAt: string | null;
    url: string;
};

export type SimpleMeta = {
    currentPage: number;
    lastPage: number;
    total: number;
    from: number | null;
    to: number | null;
};

export type ReviewerStatsRow = {
    id: number;
    name: string;
    email: string;
    avatarUrl: string | null;
    degree: string | null;
    invited: number;
    accepted: number;
    declined: number;
    completed: number;
    pending: number;
    overdue: number;
    avgDays: number | null;
    onTime: number | null;
};

export type ReviewerStats = {
    rows: ReviewerStatsRow[];
    totals: {
        reviewers: number;
        invited: number;
        completed: number;
        declined: number;
        pending: number;
        overdue: number;
        avgDays: number | null;
    };
};

export type ReportExportLink = { type: string; label: string; url: string };

export type ReportsPageProps = {
    filters: ReportFilters;
    subjects: ReportOption[];
    kpis: ReportKpi[];
    quick: ReportQuickItem[];
    system: ReportSystem;
    topAuthors: ReportTopAuthor[];
    dynamics: ReportDynamics | null;
    subjectsChart: ReportShare | null;
    countries: ReportShare | null;
    organizations: ReportOrganizations | null;
    revenue: ReportRevenue | null;
    ai: ReportAi | null;
    articles: { data: ReportArticleRow[]; meta: SimpleMeta } | null;
    reviewers: ReviewerStats | null;
    exports: ReportExportLink[];
    printUrl: string;
};

// Audit log

export type AuditSeverity = 'info' | 'warning' | 'danger';

export type AuditFilters = {
    q: string | null;
    category: string | null;
    event: string | null;
    severity: AuditSeverity | null;
    user: number | null;
    from: string | null;
    to: string | null;
};

export type AuditLogRow = {
    id: number;
    event: string;
    label: string;
    category: string;
    severity: AuditSeverity;
    description: string | null;
    subject: {
        type: string;
        id: number | null;
        label: string | null;
        url: string | null;
    } | null;
    user: {
        id: number;
        name: string;
        email: string;
        role: string | null;
        avatarUrl: string | null;
        deleted: boolean;
    } | null;
    properties: Record<string, unknown> | null;
    ip: string | null;
    userAgent: string | null;
    createdAt: string;
};

export type AuditPageProps = {
    filters: AuditFilters;
    logs: { data: AuditLogRow[]; meta: SimpleMeta };
    stats: {
        today: number;
        failedLogins: number;
        activeUsers: number;
        total: number;
    };
    categories: { value: string; label: string }[];
    events: { value: string; label: string; category: string }[];
    users: { value: number; label: string }[];
    retentionDays: number;
    exportUrl: string;
};
