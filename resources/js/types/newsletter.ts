import type { Paginated } from './users';

/** Admin → Obuna (App\Http\Controllers\Admin\Newsletter\NewsletterController) */
export type NewsletterAudience = {
    value: 'all' | 'uz' | 'ru' | 'en';
    label: string;
    count: number;
};

export type NewsletterTemplate = {
    subject: string;
    body: string;
    button_label: string;
    button_url: string;
};

export type NewsletterIssueOption = {
    id: number;
    label: string;
    /** Bu son haqida xat allaqachon yuborilgan */
    announced: boolean;
    template: NewsletterTemplate;
};

export type NewsletterCampaignItem = {
    uuid: string;
    kind: 'manual' | 'issue';
    subject: string;
    body: string;
    buttonUrl: string | null;
    locale: string | null;
    status: 'queued' | 'sending' | 'sent' | 'failed';
    recipients: number;
    sent: number;
    sender: string | null;
    createdAt: string | null;
    error: string | null;
};

export type NewsletterSubscriberState =
    | 'confirmed'
    | 'pending'
    | 'unsubscribed';

export type NewsletterSubscriberRow = {
    id: number;
    email: string;
    locale: string;
    state: NewsletterSubscriberState;
    createdAt: string | null;
    confirmedAt: string | null;
    unsubscribedAt: string | null;
};

export type NewsletterPageProps = {
    tab: 'compose' | 'subscribers';
    stats: {
        confirmed: number;
        pending: number;
        unsubscribed: number;
        campaigns: number;
    };
    audiences: NewsletterAudience[];
    issues: NewsletterIssueOption[];
    history: NewsletterCampaignItem[];
    autoIssue: boolean;
    filters: { q: string | null; status: NewsletterSubscriberState | null };
    subscribers: Pick<Paginated<NewsletterSubscriberRow>, 'data' | 'meta'>;
};
