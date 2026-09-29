/**
 * Sana, son va fayl hajmini o'zbekcha ko'rinishda formatlash.
 * (Intl'ning uz lokali brauzerlarda bir xil emas — shuning uchun qo'lda.)
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

/** 29-sentabr, 2026 */
export function formatDateLong(
    value: string | Date | null | undefined,
): string {
    const date = value ? toDate(value) : null;

    return date
        ? `${date.getDate()}-${MONTHS_LONG[date.getMonth()]}, ${date.getFullYear()}`
        : '';
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
        month: MONTHS_SHORT[date.getMonth()],
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
