/**
 * Admin → To'lovlar — App\Http\Controllers\Admin\Payments\PaymentController bilan mos.
 */
import type { PaymentsMonthly, RecentPayment } from './dashboard';
import type { Paginated } from './users';

export type PaymentTab =
    | 'awaiting'
    | 'all'
    | 'click'
    | 'payme'
    | 'manual'
    | 'failed';

export type PaymentProviderKey = 'click' | 'payme' | 'manual';

export type PaymentListItem = {
    id: number;
    uuid: string;
    receipt: string;
    reference: string | null;
    purpose: string;
    purposeLabel: string;
    article: { uuid: string; title: string } | null;
    user: { name: string; email: string };
    items: { name: string; quantity: number; total: number }[];
    amount: number;
    currency: string;
    provider: PaymentProviderKey;
    providerLabel: string;
    status: string;
    statusLabel: string;
    paidAt: string | null;
    createdAt: string | null;
    confirmedBy: string | null;
    note: string | null;
    proofName: string | null;
    proofUrl: string | null;
};

export type AwaitingPaymentItem = {
    uuid: string;
    title: string;
    subject: string | null;
    type: string;
    amountDue: number;
    currency: string;
    author: { name: string; email: string };
    submittedAt: string | null;
    waitingDays: number | null;
    urls: { confirm: string; waive: string };
};

type ProviderStat = { amount: number; count: number; share: number | null };

export type PaymentStats = {
    revenue: {
        amount: number;
        count: number;
        month: number;
        trend: number | null;
    };
    click: ProviderStat;
    payme: ProviderStat;
    manual: ProviderStat;
    awaiting: { amount: number; count: number };
};

export type ProviderBreakdown = {
    total: number;
    items: { key: PaymentProviderKey; label: string; value: number }[];
};

export type AdminPaymentsProps = {
    filters: { tab: PaymentTab; search: string | null };
    counts: Record<PaymentTab, number>;
    stats: PaymentStats;
    monthly: PaymentsMonthly;
    breakdown: ProviderBreakdown;
    recent: RecentPayment[];
    awaiting: Paginated<AwaitingPaymentItem> | null;
    payments: Paginated<PaymentListItem> | null;
    can: { confirm: boolean };
};
