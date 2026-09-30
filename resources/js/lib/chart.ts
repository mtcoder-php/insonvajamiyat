/**
 * Kichik SVG grafiklar uchun yordamchilar (kutubxonasiz).
 */

/** O'q uchun "chiroyli" maksimum va qadamlar: 0, 50, 100, 150, 200 */
export function niceScale(
    max: number,
    ticks = 4,
): { max: number; ticks: number[] } {
    if (max <= 0) {
        return {
            max: ticks,
            ticks: Array.from({ length: ticks + 1 }, (_, i) => i),
        };
    }

    const rough = max / ticks;
    const magnitude = 10 ** Math.floor(Math.log10(rough));
    const residual = rough / magnitude;
    const step =
        (residual <= 1
            ? 1
            : residual <= 2
              ? 2
              : residual <= 2.5
                ? 2.5
                : residual <= 5
                  ? 5
                  : 10) * magnitude;
    const niceMax = step * Math.ceil(max / step);

    return {
        max: niceMax,
        ticks: Array.from(
            { length: Math.round(niceMax / step) + 1 },
            (_, i) => i * step,
        ),
    };
}

/** Faqat yuqori burchaklari yumaloq ustun (asosi o'qqa tegib turadi) */
export function roundedBar(
    x: number,
    y: number,
    width: number,
    height: number,
    radius = 4,
): string {
    if (height <= 0) {
        return '';
    }

    const r = Math.min(radius, width / 2, height);

    return [
        `M${x},${y + height}`,
        `V${y + r}`,
        `Q${x},${y} ${x + r},${y}`,
        `H${x + width - r}`,
        `Q${x + width},${y} ${x + width},${y + r}`,
        `V${y + height}`,
        'Z',
    ].join(' ');
}

/** Silliq egri chiziq (monoton kubik) — nuqtalar bo'yicha SVG path */
export function smoothLine(points: [number, number][]): string {
    if (points.length === 0) {
        return '';
    }

    let d = `M${points[0][0]},${points[0][1]}`;

    for (let i = 1; i < points.length; i++) {
        const [x0, y0] = points[i - 1];
        const [x1, y1] = points[i];
        const cx = (x0 + x1) / 2;
        d += ` C${cx},${y0} ${cx},${y1} ${x1},${y1}`;
    }

    return d;
}
