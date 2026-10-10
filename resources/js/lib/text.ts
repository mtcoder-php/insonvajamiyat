/**
 * Oddiy matnni xatboshilarga bo'lish: bo'sh qator (ichida bo'shliq bo'lishi ham mumkin)
 * bilan ajratilgan qismlar. Windows qator oxirlari (\r\n) ham hisobga olinadi — rasmli
 * forma (multipart) yuborilganda brauzer qatorlarni \r\n ga aylantiradi.
 */
export function toParagraphs(text: string | null | undefined): string[] {
    return (text ?? '')
        .replace(/\r\n?/g, '\n')
        .split(/\n[^\S\n]*\n/)
        .map((paragraph) => paragraph.trim())
        .filter(Boolean);
}

/** Taqqoslash uchun: bo'shliqlar bitta probelga keltiriladi */
export function sameText(
    a: string | null | undefined,
    b: string | null | undefined,
): boolean {
    const normalize = (value: string | null | undefined) =>
        (value ?? '').replace(/\s+/g, ' ').trim();

    return normalize(a) !== '' && normalize(a) === normalize(b);
}
