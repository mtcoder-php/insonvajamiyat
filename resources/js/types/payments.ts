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
    | 'failed'
    | 'refunds'
    | 'logs';

export type PaymentLogFilter = 'all' | 'errors' | 'signature';

/** Click / Payme so'rovlari jurnali qatori (App\\Http\\Resources\\Admin\\PaymentLogResource) */
export type PaymentLogItem = {
    id: number;
    provider: PaymentProviderKey;
    providerLabel: string;
    action: string;
    ok: boolean;
    errorCode: number | null;
    signatureValid: boolean | null;
    httpStatus: number | null;
    ip: string | null;
    durationMs: number | null;
    createdAt: string | null;
    payment: {
        uuid: string;
        receipt: string;
        transaction: string | null;
    } | null;
    request: Record<string, unknown>;
    response: Record<string, unknown> | null;
};

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
    refundedAt: string | null;
    /** Qaytarish: blocked — mumkin emasligi sababi (null — mumkin); history — so'rovlar tarixi */
    refund: {
        blocked: string | null;
        url: string;
        history: RefundItem[];
    } | null;
};

export type RefundStatusKey =
    | 'requested'
    | 'processing'
    | 'completed'
    | 'failed';

/** Qaytarish — App\Http\Resources\Admin\RefundResource */
export type RefundItem = {
    id: number;
    amount: number;
    reason: string;
    status: RefundStatusKey;
    statusLabel: string;
    open: boolean;
    reference: string | null;
    error: string | null;
    requestedBy: string;
    processedBy: string | null;
    createdAt: string | null;
    processedAt: string | null;
    payment: {
        uuid: string;
        receipt: string;
        transaction: string | null;
        provider: PaymentProviderKey;
        providerLabel: string;
        user: { name: string; email: string };
        article: string | null;
    };
    awaitingProvider: boolean;
    urls: { cancel: string | null };
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
    /** To'lov eslatmalari: soni, oxirgisi, keyingisi qachondan mumkin (null — hozir) */
    reminders: {
        count: number;
        lastAt: string | null;
        availableAt: string | null;
    };
    urls: { remind: string; confirm: string; waive: string };
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
    filters: { tab: PaymentTab; search: string | null; log: PaymentLogFilter };
    counts: Record<PaymentTab, number>;
    stats: PaymentStats;
    monthly: PaymentsMonthly;
    breakdown: ProviderBreakdown;
    recent: RecentPayment[];
    awaiting: Paginated<AwaitingPaymentItem> | null;
    payments: Paginated<PaymentListItem> | null;
    refunds: Paginated<RefundItem> | null;
    logs: Paginated<PaymentLogItem> | null;
    can: { confirm: boolean; refund: boolean };
    remindAllUrl: string;
    reminderDays: number[];
};
