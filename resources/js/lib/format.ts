import { intlLocale, locale, SOURCE_LOCALE, t, tc } from '@/lib/i18n';

/**
 * Sana, son va fayl hajmini joriy tilda formatlash.
 * O'zbekcha — qo'lda (Intl'ning uz lokali brauzerlarda bir xil emas),
 * rus va ingliz tillari — Intl.DateTimeFormat orqali.
 */
export const MONTHS_SHORT = [
    'Yan',
    'Fev',
    'Mar',
    'Apr',
    'May',
    'Iyn',
    'Iyl',
    'Avg',
    'Sen',
    'Okt',
    'Noy',
    'Dek',
] as const;

export const MONTHS_LONG = [
    'yanvar',
    'fevral',
    'mart',
    'aprel',
    'may',
    'iyun',
    'iyul',
    'avgust',
    'sentabr',
    'oktabr',
    'noyabr',
    'dekabr',
] as const;

/** Oy nomi joriy tilda: "sentabr" / "сентября" / "September" */
function monthName(date: Date, style: 'long' | 'short'): string {
    if (locale() === SOURCE_LOCALE) {
        return (style === 'long' ? MONTHS_LONG : MONTHS_SHORT)[date.getMonth()];
    }

    return new Intl.DateTimeFormat(intlLocale(), { month: style })
        .format(date)
        .replace('.', '');
}

function toDate(value: string | Date): Date | null {
    // "2026-09-29" — mahalliy vaqt bo'yicha (UTC siljishisiz) o'qiladi
    const date =
        typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value)
            ? new Date(`${value}T00:00:00`)
            : new Date(value);

    return Number.isNaN(date.getTime()) ? null : date;
}

/** 29.09.2026 */
export function formatDate(value: string | Date | null | undefined): string {
    const date = value ? toDate(value) : null;

    if (!date) {
        return '';
    }

    const dd = String(date.getDate()).padStart(2, '0');
    const mm = String(date.getMonth() + 1).padStart(2, '0');

    return `${dd}.${mm}.${date.getFullYear()}`;
}

/** 29-sentabr, 2026 · 29 сентября 2026 г. · 29 September 2026 */
export function formatDateLong(
    value: string | Date | null | undefined,
): string {
    const date = value ? toDate(value) : null;

    if (!date) {
        return '';
    }

    if (locale() !== SOURCE_LOCALE) {
        return new Intl.DateTimeFormat(intlLocale(), {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        }).format(date);
    }

    return `${date.getDate()}-${MONTHS_LONG[date.getMonth()]}, ${date.getFullYear()}`;
}

/** { day: '15', month: 'Okt', year: 2026 } — tadbirlar kalendari uchun */
export function dateParts(value: string): {
    day: string;
    month: string;
    year: number;
} {
    const date = toDate(value) ?? new Date();

    return {
        day: String(date.getDate()).padStart(2, '0'),
        month: monthName(date, 'short'),
        year: date.getFullYear(),
    };
}

/** 12 345 */
export function formatNumber(value: number): string {
    return String(Math.trunc(value)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
}

/** 12.4 MB */
export function formatFileSize(bytes: number | null | undefined): string {
    if (!bytes || bytes <= 0) {
        return '';
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    const exponent = Math.min(
        Math.floor(Math.log(bytes) / Math.log(1024)),
        units.length - 1,
    );
    const size = bytes / 1024 ** exponent;

    return `${size.toFixed(exponent === 0 ? 0 : 1)} ${units[exponent]}`;
}

/** "10 daqiqa oldin", "3 soat oldin", "2 kun oldin" */
export function timeAgo(value: string | null | undefined): string {
    if (!value) {
        return '';
    }

    const seconds = Math.max(
        0,
        Math.round((Date.now() - new Date(value).getTime()) / 1000),
    );

    if (seconds < 60) {
        return t('hozirgina');
    }

    const minutes = Math.round(seconds / 60);

    if (minutes < 60) {
        return tc(':count daqiqa oldin', minutes);
    }

    const hours = Math.round(minutes / 60);

    if (hours < 24) {
        return tc(':count soat oldin', hours);
    }

    const days = Math.round(hours / 24);

    return days < 30 ? tc(':count kun oldin', days) : formatDate(value);
}

/** 28.06.2026 10:15 */
/** ISO → "14:32" (bo'sh/yaroqsiz qiymat — "") */
export function formatTime(value: string | null | undefined): string {
    const date = value ? new Date(value) : null;

    if (!date || Number.isNaN(date.getTime())) {
        return '';
    }

    return `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
}

export function formatDateTime(value: string | null | undefined): string {
    const date = value ? new Date(value) : null;

    if (!date || Number.isNaN(date.getTime())) {
        return '';
    }

    const time = `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;

    return `${formatDate(date)} ${time}`;
}

/** 20000000 → "20M", 150000 → "150K" (grafik o'qlari uchun) */
export function formatCompact(value: number): string {
    if (value >= 1_000_000) {
        return `${Number((value / 1_000_000).toFixed(1))}M`;
    }

    if (value >= 1_000) {
        return `${Number((value / 1_000).toFixed(1))}K`;
    }

    return String(value);
}

/** 150000 → "150 000 so'm" */
export function formatSum(value: number): string {
    return `${formatNumber(value)} so'm`;
}

/** +998901234567 → "+998 90 123 45 67" (boshqa formatlar o'zgarishsiz) */
export function formatPhone(value: string | null | undefined): string {
    if (!value) {
        return '';
    }

    const match = /^\+998(\d{2})(\d{3})(\d{2})(\d{2})$/.exec(value);

    return match
        ? `+998 ${match[1]} ${match[2]} ${match[3]} ${match[4]}`
        : value;
}

/** Tadbir sanasi: { day: 13, month: "Oktabr", year: 2026 } */
export function eventDateParts(value: string): {
    day: number;
    month: string;
    year: number;
} {
    const date = new Date(value);
    const month =
        locale() === SOURCE_LOCALE
            ? MONTHS_LONG[date.getMonth()]
            : new Intl.DateTimeFormat(intlLocale(), {
                  month: 'long',
              }).format(date);

    return {
        day: date.getDate(),
        month: month.charAt(0).toUpperCase() + month.slice(1),
        year: date.getFullYear(),
    };
}
