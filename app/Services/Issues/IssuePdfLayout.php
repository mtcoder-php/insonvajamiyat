<?php

namespace App\Services\Issues;

use App\Models\JournalIssue;
use App\Models\Subject;
use App\Support\MediaUrl;
use App\Support\Pdf\SimplePdf;
use App\Support\Translations;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Throwable;

/**
 * Son PDF ining jurnal tomonidan chiziladigan qismlari (tahririyat maketi asosida):
 *
 *  - muqova (son uchun tayyor muqova yuklanmagan bo'lsa): to'q ko'k fon, oltin ramka va naqshlar,
 *    logotip, jurnal nomi, shior, ark ichida umumiy rasm, son raqami, yil va ISSN;
 *  - 2-bet "Jurnal haqida": tavsif, asosiy yo'nalishlar (6 karta, ikonkali), "Jurnal ma'lumotlari"
 *    bloki (rekvizitlar, ISSN, DOI, saytga QR-kod);
 *  - mundarija (son oxirida): ruknlar, sarlavha, mualliflar, nuqtali chiziq bilan bet raqami;
 *  - har bir ichki betga: krem fon (underlay), qo'sh oltin ramka va burchak naqshlari, tepada logotip +
 *    jurnal nomi + son ("№ 4 (40) • 2026"), pastda oltin chiziq, shior va bet raqami (overlay).
 *
 * Maketchi maqola PDF ini fonsiz (oq/shaffof) va chetlarida bo'sh joy bilan tayyorlaydi (MARGINS).
 */
class IssuePdfLayout
{
    public const NAVY = [0.031, 0.118, 0.235];

    public const GOLD = [0.788, 0.635, 0.302];

    private const GOLD_DARK = [0.62, 0.48, 0.2];

    private const INK = [0.12, 0.16, 0.24];

    private const MUTED = [0.4, 0.45, 0.53];

    private const CREAM = [0.988, 0.976, 0.953];

    private const PAPER = [1.0, 0.996, 0.988];

    private const BAR = [0.945, 0.918, 0.859];

    /** Sahifa turlari (overlay/underlay uchun) */
    public const INFO = 'info';

    public const TOC = 'toc';

    public const ARTICLE = 'article';

    /** Ramka (sahifa chetidan, pt) */
    private const FRAME = 20.0;

    private const HEADER_LINE = 76.0;

    private const FOOTER_LINE = 60.0;

    /** Kontent maydoni (A4): chap/o'ng, tepa, past — maketchi uchun ham shu */
    public const MARGINS = ['side' => 56.0, 'top' => 104.0, 'bottom' => 82.0];

    /** Yo'nalish nomidagi kalit so'z → ikonka */
    private const SUBJECT_ICONS = [
        'tarix' => 'landmark', 'istor' => 'landmark', 'histor' => 'landmark',
        'etno' => 'users', 'antrop' => 'users-round', 'sotsio' => 'users-round', 'ijtimoiy' => 'users',
        'falsaf' => 'scale', 'philos' => 'scale', 'huquq' => 'scale', 'siyos' => 'scale',
        'filolog' => 'book-open', 'til' => 'languages', 'adabiy' => 'book-open',
        'madaniyat' => 'palette', 'san\'at' => 'palette', 'cultur' => 'palette',
        'ta\'lim' => 'graduation-cap', 'pedagog' => 'graduation-cap', 'psixolog' => 'brain',
        'iqtisod' => 'trending-up', 'econom' => 'trending-up', 'axborot' => 'cpu', 'raqamli' => 'cpu', 'texnolog' => 'cpu',
        'manba' => 'scroll-text', 'arxeolog' => 'library',
    ];

    private const FALLBACK_ICONS = ['users', 'book-open', 'trending-up', 'scale', 'landmark', 'cpu'];

    /**
     * Muqova: son uchun alohida muqova yuklanmagan bo'lsa — avtomatik dizayn.
     *
     * @param  string|null  $art  ark ichidagi rasm (umumiy muqova rasmi)
     */
    public function coverPage(JournalIssue $issue, ?string $art): string
    {
        $pdf = new SimplePdf;
        $page = $pdf->addPage();
        $w = SimplePdf::A4_WIDTH;
        $h = SimplePdf::A4_HEIGHT;
        $center = $w / 2;
        $white = [1.0, 1.0, 1.0];

        // Fon: yuqoridan pastga to'q ko'k gradient
        $steps = 60;

        for ($i = 0; $i < $steps; $i++) {
            $t = $i / ($steps - 1);
            $color = [0.055 - 0.03 * $t, 0.16 - 0.06 * $t, 0.33 - 0.12 * $t];
            $pdf->rect($page, 0, $h - ($i + 1) * $h / $steps, $w, $h / $steps + 0.6, $color);
        }

        $this->frame($pdf, $page, $w, $h, 1.4);

        // Tepada: nashr turi (chapda) va ISSN (o'ngda)
        $kind = mb_strtoupper($this->kind());
        $y = $h - 62;

        foreach (SimplePdf::wrap($kind, 120, 9, 'F5') as $line) {
            $pdf->text($page, 52, $y, $line, 9, 'F5', 'left', $white, 0.8);
            $y -= 12;
        }

        $issn = (string) config('journal.issn');

        if ($issn !== '') {
            $pdf->text($page, $w - 52, $h - 62, 'ISSN: '.$issn, 9, 'F5', 'right', $white, 0.6);
        }

        // Logotip
        $logo = $this->asset('images/logo-mark-light.webp');

        if ($logo !== null) {
            $pdf->addImage('logo-light', $logo);
            $pdf->drawImage($page, 'logo-light', $center - 32, $h - 168, 64, 64);
        }

        // Jurnal nomi: birinchi so'zlar oq, oxirgisi oltin
        $words = preg_split('/\s+/u', mb_strtoupper(trim((string) config('journal.name')))) ?: [];
        $last = count($words) > 1 ? (string) array_pop($words) : '';
        $first = implode(' ', $words);
        $pdf->text($page, $center, $h - 232, $first, 46, 'F4', 'center', $white, 1.5);

        if ($last !== '') {
            $pdf->text($page, $center, $h - 290, $last, 56, 'F4', 'center', self::GOLD, 2.0);
        }

        $pdf->text($page, $center, $h - 320, $kind, 11, 'F5', 'center', $white, 1.4);
        $this->divider($pdf, $page, $center - 150, $center + 150, $h - 336, 0.7);
        $pdf->text($page, $center, $h - 362, $this->slogan(), 14, 'F3', 'center', $white);

        // Ark ichidagi rasm
        $archX = 92.0;
        $archW = $w - 2 * $archX;
        $archBottom = 138.0;
        $radius = $archW / 2;
        $archTop = $h - 412;
        $springY = $archTop - $radius;
        $arch = $this->archPath($archX, $archBottom, $archW, $springY, $radius);

        if ($art !== null) {
            try {
                [$pw, $ph] = $pdf->addImage('cover-art', $art);
                $boxH = $archTop - $archBottom;
                $scale = max($archW / $pw, $boxH / $ph);
                $pdf->clip($page, $arch);
                $pdf->drawImage($page, 'cover-art', $center - $pw * $scale / 2, $archBottom + ($boxH - $ph * $scale) / 2, $pw * $scale, $ph * $scale);
                // Pastki qismi fonga sekin singadi
                for ($i = 0; $i < 16; $i++) {
                    $pdf->opacity($page, 0.06 * ($i + 1));
                    $pdf->rect($page, $archX, $archBottom + 60 - $i * 4, $archW, 4.2, [0.025, 0.1, 0.21]);
                    $pdf->restore($page);
                }
                $pdf->restore($page);
            } catch (Throwable) {
                // Rasm o'qilmasa — ark bo'sh qoladi
            }
        }

        $pdf->path($page, $arch, null, self::GOLD, 1.2);
        $pdf->path($page, $this->archPath($archX - 6, $archBottom - 6, $archW + 12, $springY, $radius + 6), null, self::GOLD, 0.5);

        // Ark ustida yoy bo'ylab yulduzchalar (o'rtadagisi kattaroq)
        foreach ([30, 50, 70, 90, 110, 130, 150] as $degrees) {
            $angle = deg2rad($degrees);
            $size = $degrees === 90 ? 8.0 : ($degrees % 40 === 10 ? 4.5 : 6.0);
            $this->star($pdf, $page, $center + cos($angle) * ($radius + 20), $springY + sin($angle) * ($radius + 20), $size);
        }

        // Pastda: son raqami, yil, umumiy tartib raqami, shahar
        $pdf->text($page, $center, 100, '№ '.$issue->number.'   |   '.$issue->year, 22, 'F4', 'center', self::GOLD, 0.8);
        $pdf->text($page, $center, 80, '('.$this->serial($issue).'-son)', 11, 'F5', 'center', $white);
        $pdf->text($page, $center, 58, $this->city(), 10, 'F5', 'center', $white, 0.5);

        return $pdf->output();
    }

    /**
     * 2-bet: "Jurnal haqida" (krem fon bilan; ramka va kolontitul overlay bilan qo'yiladi).
     */
    public function infoPage(JournalIssue $issue): string
    {
        $pdf = new SimplePdf;
        $page = $pdf->addPage();
        $w = SimplePdf::A4_WIDTH;
        $h = SimplePdf::A4_HEIGHT;
        $x = self::MARGINS['side'];
        $width = $w - 2 * $x;
        $center = $w / 2;

        $pdf->rect($page, 0, 0, $w, $h, self::CREAM);

        $y = $h - self::MARGINS['top'] - 18;
        $pdf->text($page, $center, $y, 'JURNAL HAQIDA', 22, 'F4', 'center', self::NAVY, 1.2);
        $y -= 32;

        $name = (string) config('journal.name');
        $about = Translations::line(config('journal.pdf.about'));
        $intro = is_string($about) && $about !== ''
            ? $about
            : '«'.$name.'» '.mb_strtolower($this->kind()).' — inson, jamiyat, ta’lim, madaniyat va ijtimoiy taraqqiyot bilan bog‘liq dolzarb muammolarni o‘rganishga bag‘ishlangan ilmiy nashrdir.'
                .$this->sentence(Translations::line(config('journal.description')));
        $y = $pdf->paragraph($page, $x + 10, $y, $width - 20, $intro, 11, 'F5', 1.5, self::INK, 'justify', 18) - 16;

        // Asosiy yo'nalishlar — 3×2 karta
        $pdf->text($page, $center, $y, 'ASOSIY YO‘NALISHLAR', 10, 'F4', 'center', self::NAVY, 1.0);
        $y -= 16;
        $gap = 12.0;
        $cardW = ($width - 2 * $gap) / 3;
        $cardH = 86.0;

        foreach ($this->directions() as $i => [$icon, $title]) {
            $cx = $x + ($i % 3) * ($cardW + $gap);
            $cy = $y - intdiv($i, 3) * ($cardH + $gap) - $cardH;
            $pdf->roundRect($page, $cx, $cy, $cardW, $cardH, 6, self::PAPER, self::GOLD, 0.7);
            $pdf->icon($page, $icon, $cx + $cardW / 2 - 11, $cy + $cardH - 12, 22, self::NAVY, 2.2);
            $lines = array_slice(SimplePdf::wrap($title, $cardW - 20, 8.5, 'F5'), 0, 2);
            $ty = $cy + 30 - (count($lines) - 1) * -5.5;

            foreach ($lines as $line) {
                $pdf->text($page, $cx + $cardW / 2, $ty, $line, 8.5, 'F5', 'center', self::INK);
                $ty -= 11;
            }
        }

        $y -= 2 * $cardH + $gap + 20;

        // Jurnal ma'lumotlari — to'q ko'k blok
        $this->infoBox($pdf, $page, $issue, $x, $y, $width);

        return $pdf->output();
    }

    /**
     * Mundarija (son oxirida): ruknlar → maqola, mualliflar, bet raqami.
     *
     * @param  array<int, array{section: string|null, articles: array<int, array{title: string, authors: string, pages: string|null}>}>  $groups
     */
    public function tocPages(JournalIssue $issue, array $groups): string
    {
        $pdf = new SimplePdf;
        $w = SimplePdf::A4_WIDTH;
        $h = SimplePdf::A4_HEIGHT;
        $x = self::MARGINS['side'] + 6;
        $width = $w - 2 * $x;
        $numberCol = 40.0;
        $bottom = self::MARGINS['bottom'] + 8;
        $top = $h - self::MARGINS['top'] - 8;

        $newPage = function () use ($pdf, $w, $h): int {
            $page = $pdf->addPage();
            $pdf->rect($page, 0, 0, $w, $h, self::CREAM);

            return $page;
        };

        $page = $newPage();
        $y = $top - 10;
        $pdf->text($page, $w / 2, $y, 'MUNDARIJA', 22, 'F4', 'center', self::NAVY, 1.2);
        $this->divider($pdf, $page, $w / 2 - 70, $w / 2 + 70, $y - 14, 0.6);
        $y -= 44;

        foreach ($groups as $group) {
            if ($group['section'] !== null) {
                if ($y < $bottom + 60) {
                    $page = $newPage();
                    $y = $top;
                }

                $pdf->roundRect($page, $x, $y - 6, $width, 20, 3, self::BAR);
                $pdf->rect($page, $x, $y - 6, 3, 20, self::GOLD);
                $pdf->text($page, $x + 12, $y, mb_strtoupper($group['section']), 8.5, 'F4', 'left', self::NAVY, 0.6);
                $y -= 28;
            }

            foreach ($group['articles'] as $article) {
                $titleLines = SimplePdf::wrap($article['title'], $width - $numberCol - 14, 11, 'F4');
                $authorLines = $article['authors'] !== '' ? SimplePdf::wrap($article['authors'], $width - $numberCol - 14, 9, 'F3') : [];
                $needed = count($titleLines) * 14.5 + count($authorLines) * 12 + 12;

                if ($y - $needed < $bottom) {
                    $page = $newPage();
                    $y = $top;
                }

                foreach ($titleLines as $i => $line) {
                    $pdf->text($page, $x, $y, $line, 11, 'F4', 'left', self::NAVY);

                    if ($i === count($titleLines) - 1 && $article['pages'] !== null) {
                        // Nuqtali chiziq sarlavha oxiridan bet raqamigacha
                        $from = $x + SimplePdf::width($line, 11, 'F4') + 6;
                        $to = $x + $width - $numberCol;

                        if ($to - $from > 12) {
                            $pdf->text($page, $from, $y, str_repeat('.', (int) (($to - $from) / 3.1)), 9, 'F5', 'left', [0.7, 0.62, 0.45]);
                        }

                        $pdf->text($page, $x + $width, $y, (string) $article['pages'], 10.5, 'F4', 'right', self::NAVY);
                    }

                    $y -= 14.5;
                }

                foreach ($authorLines as $line) {
                    $pdf->text($page, $x, $y + 1, $line, 9, 'F3', 'left', self::MUTED);
                    $y -= 12;
                }

                $y -= 10;
            }
        }

        return $pdf->output();
    }

    /**
     * Bo'sh A4 sahifa (muqova topilmasa — betlar hisobi buzilmasligi uchun).
     */
    public function blankPage(): string
    {
        $pdf = new SimplePdf;
        $pdf->addPage();

        return $pdf->output();
    }

    /**
     * Ichki betlar ostiga qo'yiladigan krem fon (qpdf --underlay). Maqola PDF i oq fon chizmagan
     * bo'lsa — sahifa krem rangda ko'rinadi.
     *
     * @param  array<int, array{0: float, 1: float}>  $sizes
     * @param  array<int, string|null>  $kinds  sahifa → INFO | TOC | ARTICLE | null (hech narsa)
     */
    public function underlay(array $sizes, array $kinds): string
    {
        $pdf = new SimplePdf;

        foreach ($sizes as $index => [$width, $height]) {
            $page = $pdf->addPage($width, $height);

            if (($kinds[$index] ?? null) !== null) {
                $pdf->rect($page, 0, 0, $width, $height, self::CREAM);
            }
        }

        return $pdf->output();
    }

    /**
     * Birlashtirilgan fayl ustiga bosiladigan sahifalar: qo'sh ramka, naqshlar, kolontitul, bet raqami.
     *
     * @param  array<int, array{0: float, 1: float}>  $sizes  har bir sahifa o'lchami (pt)
     * @param  array<int, string|null>  $kinds  sahifa → INFO | TOC | ARTICLE | null (hech narsa bosilmaydi)
     */
    public function overlay(JournalIssue $issue, array $sizes, array $kinds): string
    {
        $pdf = new SimplePdf;
        $journal = mb_strtoupper((string) config('journal.name'));
        $issueLabel = '№ '.$issue->number.' ('.$this->serial($issue).') • '.$issue->year;
        $logo = $this->asset('images/logo-mark.webp');

        if ($logo !== null) {
            $pdf->addImage('logo', $logo);
        }

        foreach ($sizes as $index => [$width, $height]) {
            $page = $pdf->addPage($width, $height);
            $kind = $kinds[$index] ?? null;

            if ($kind === null) {
                continue;
            }

            $this->frame($pdf, $page, $width, $height, 1.0);

            // Kolontitul: logotip + jurnal nomi (chapda), son (o'ngda), oltin chiziq
            $left = self::FRAME + 26;
            $right = $width - self::FRAME - 26;
            $top = $height - self::FRAME - 18;

            if ($logo !== null) {
                $pdf->drawImage($page, 'logo', $left, $top - 26, 26, 26);
            }

            if ($kind === self::ARTICLE) {
                $pdf->text($page, $left + 34, $top - 16, $journal, 10, 'F4', 'left', self::NAVY, 0.6);
                $pdf->text($page, $right, $top - 10, $this->kind(), 7.5, 'F3', 'right', self::NAVY);
                $pdf->text($page, $right, $top - 21, $issueLabel, 8, 'F4', 'right', self::NAVY);
            } else {
                $pdf->text($page, $left + 34, $top - 11, $journal, 11, 'F4', 'left', self::NAVY, 0.6);
                $pdf->text($page, $left + 34, $top - 22, mb_strtoupper($this->kind()), 6.5, 'F5', 'left', self::GOLD_DARK, 0.8);
                $pdf->text($page, $right, $top - 11, $issueLabel, 9, 'F4', 'right', self::NAVY);
                $pdf->text($page, $right, $top - 22, $this->city(), 8, 'F5', 'right', self::MUTED);
            }

            $this->divider($pdf, $page, $left, $right, $height - self::HEADER_LINE, 0.7);

            // Pastki qism: oltin chiziq, shior (o'rtada) va bet raqami (o'ngda)
            $this->divider($pdf, $page, $left, $right, self::FOOTER_LINE, 0.7);
            $footer = $kind === self::INFO ? (string) config('journal.pdf.motto', '') : $this->slogan();

            if ($footer !== '') {
                $pdf->text($page, $width / 2, self::FOOTER_LINE - 17, $footer, $kind === self::INFO ? 10 : 8.5, 'F3', 'center', self::NAVY);
            }

            if ($kind !== self::INFO) {
                $pdf->text($page, $right, self::FOOTER_LINE - 17, (string) ($index + 1), 10, 'F4', 'right', self::NAVY);
            }
        }

        return $pdf->output();
    }

    /** "2026-yil, 3-son (12-jild)" */
    public function issueLine(JournalIssue $issue): string
    {
        return $issue->year.'-yil, '.$issue->number.'-son'.($issue->volume ? ' ('.$issue->volume.'-jild)' : '');
    }

    /**
     * Jurnalning umumiy tartib raqami: tizimdagi sonlar (yil, raqam bo'yicha) + oldingi sonlar
     * (journal.pdf.issue_offset — tizimdan oldin chiqqan sonlar soni).
     */
    public function serial(JournalIssue $issue): int
    {
        $before = JournalIssue::query()
            ->where(fn ($q) => $q->where('year', '<', $issue->year)
                ->orWhere(fn ($q) => $q->where('year', $issue->year)->where('number', '<', $issue->number)))
            ->count();

        return (int) config('journal.pdf.issue_offset', 0) + $before + 1;
    }

    private function infoBox(SimplePdf $pdf, int $page, JournalIssue $issue, float $x, float $top, float $width): void
    {
        $white = [1.0, 1.0, 1.0];
        $soft = [0.78, 0.82, 0.88];
        $address = Translations::line(config('journal.contact.address'));
        $site = rtrim((string) config('app.url'), '/');

        $rows = array_values(array_filter([
            ['book-bookmark', 'Nashr nomi', (string) config('journal.name')],
            ['file-text', 'Turi', $this->kind()],
            ['clock', 'Chastotasi', Translations::line(config('journal.frequency'))],
            ['languages', 'Chop etish tili', Translations::line(config('journal.pdf.languages'))],
            ['map-pin', 'Tahririyat manzili', $address],
            ['mail', 'Elektron pochta', config('journal.contact.email')],
            ['globe', 'Veb-sayt', preg_replace('~^https?://~', '', $site)],
        ], fn (array $row): bool => is_string($row[2]) && trim($row[2]) !== ''));

        $ids = array_values(array_filter([
            ['hash', 'ISSN', config('journal.issn')],
            ['hash', 'e-ISSN', config('journal.eissn')],
            ['link', 'DOI', $issue->doi ?: config('journal.doi_prefix')],
        ], fn (array $row): bool => is_string($row[2]) && trim($row[2]) !== ''));

        $rowH = 30.0;
        $height = max(50 + count($rows) * $rowH, 240.0);
        $bottom = $top - $height;

        $pdf->roundRect($page, $x, $bottom, $width, $height, 8, self::NAVY);
        $pdf->roundRect($page, $x + 4, $bottom + 4, $width - 8, $height - 8, 6, null, self::GOLD, 0.6);
        $pdf->text($page, $x + 20, $top - 26, 'JURNAL MA’LUMOTLARI', 10, 'F4', 'left', self::GOLD, 0.8);
        $pdf->line($page, $x + 20, $top - 34, $x + $width * 0.6, $top - 34, 0.5, self::GOLD);

        $splitX = $x + $width * 0.64;
        $y = $top - 52;

        foreach ($rows as [$icon, $label, $value]) {
            $pdf->icon($page, $icon, $x + 20, $y + 8, 12, self::GOLD, 2.0);
            $pdf->text($page, $x + 42, $y, $label.':', 8, 'F4', 'left', $white);
            $pdf->text($page, $x + 42, $y - 10, (string) $value, 7.5, 'F5', 'left', $soft);
            $y -= $rowH;
        }

        // O'ng ustun: ISSN, DOI va saytga QR-kod
        $pdf->line($page, $splitX, $top - 44, $splitX, $bottom + 18, 0.5, self::GOLD);
        $rx = $splitX + 16;
        $y = $top - 52;

        foreach ($ids as [$icon, $label, $value]) {
            $pdf->icon($page, $icon, $rx, $y + 8, 13, self::GOLD, 2.0);
            $pdf->text($page, $rx + 22, $y, $label.':', 8, 'F4', 'left', $white);
            $pdf->text($page, $rx + 22, $y - 10, (string) $value, 7.5, 'F5', 'left', $soft);
            $y -= $rowH + 2;
        }

        if ($site !== '') {
            $size = 62.0;
            $qx = $splitX + ($x + $width - $splitX - $size) / 2;
            $qy = $bottom + 30;
            $this->qr($pdf, $page, $site, $qx, $qy, $size);
            $pdf->text($page, $qx + $size / 2, $qy - 14, 'Rasmiy sayt', 7.5, 'F5', 'center', $white);
        }
    }

    /**
     * QR-kod (oq fonda to'q ko'k modullar). Bacon QR (laravel/fortify bog'liqligi) matritsasi.
     */
    private function qr(SimplePdf $pdf, int $page, string $text, float $x, float $y, float $size): void
    {
        try {
            $matrix = Encoder::encode($text, ErrorCorrectionLevel::M())->getMatrix();
        } catch (Throwable) {
            return;
        }

        $modules = $matrix->getWidth();
        $pad = 4.0;
        $cell = ($size - 2 * $pad) / $modules;
        $pdf->roundRect($page, $x, $y, $size, $size, 3, [1.0, 1.0, 1.0]);
        $ops = '';

        for ($row = 0; $row < $modules; $row++) {
            $run = null;

            for ($col = 0; $col <= $modules; $col++) {
                $dark = $col < $modules && $matrix->get($col, $row) === 1;

                if ($dark && $run === null) {
                    $run = $col;
                } elseif (! $dark && $run !== null) {
                    // Gorizontal ketma-ket modullar bitta to'rtburchak
                    $ops .= sprintf('%.2F %.2F %.2F %.2F re ', $x + $pad + $run * $cell, $y + $size - $pad - ($row + 1) * $cell, ($col - $run) * $cell + 0.05, $cell + 0.05);
                    $run = null;
                }
            }
        }

        if ($ops !== '') {
            $pdf->path($page, trim($ops), self::NAVY);
        }
    }

    /**
     * Asosiy yo'nalishlar: faol yo'nalishlar (6 tagacha) va mos ikonka.
     *
     * @return list<array{0: string, 1: string}>
     */
    private function directions(): array
    {
        $names = [];

        try {
            $names = Subject::query()
                ->where('is_active', true)
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->limit(6)
                ->get()
                // PDF jurnal tilida (o'zbekcha)
                ->map(fn (Subject $subject): string => (string) ($subject->getTranslation('name', 'uz', false) ?: $subject->name))
                ->filter()
                ->values()
                ->all();
        } catch (Throwable) {
        }

        if (count($names) < 3) {
            $names = ['Ijtimoiy fanlar va falsafa', 'Ta’lim va innovatsiya', 'Iqtisodiyot va boshqaruv', 'Huquq va siyosat', 'Tarix va madaniyat', 'Axborot texnologiyalari va raqamli iqtisodiyot'];
        }

        $items = [];

        foreach (array_slice($names, 0, 6) as $i => $name) {
            $icon = self::FALLBACK_ICONS[$i % count(self::FALLBACK_ICONS)];
            $lower = mb_strtolower(str_replace(['‘', '’', 'ʻ', 'ʼ'], "'", $name));

            foreach (self::SUBJECT_ICONS as $needle => $candidate) {
                if (str_contains($lower, $needle)) {
                    $icon = $candidate;

                    break;
                }
            }

            $items[] = [$icon, $name];
        }

        return $items;
    }

    /**
     * Qo'sh oltin ramka va to'rt burchakda naqsh.
     */
    private function frame(SimplePdf $pdf, int $page, float $width, float $height, float $weight): void
    {
        $f = self::FRAME;
        $pdf->strokeRect($page, $f, $f, $width - 2 * $f, $height - 2 * $f, 0.9 * $weight, self::GOLD);
        $pdf->strokeRect($page, $f + 4, $f + 4, $width - 2 * $f - 8, $height - 2 * $f - 8, 0.4 * $weight, self::GOLD);

        foreach ([[$f + 4, $f + 4, 1, 1], [$width - $f - 4, $f + 4, -1, 1], [$f + 4, $height - $f - 4, 1, -1], [$width - $f - 4, $height - $f - 4, -1, -1]] as [$cx, $cy, $dx, $dy]) {
            $this->corner($pdf, $page, $cx, $cy, $dx, $dy, $weight);
        }
    }

    /**
     * Burchak naqshi: ichkariga qaragan chorak yoy, ikki qisqa chiziq va romb.
     */
    private function corner(SimplePdf $pdf, int $page, float $x, float $y, int $dx, int $dy, float $weight): void
    {
        $r = 16.0;
        $k = 0.5523 * $r;
        // Chorak yoy: (x + r·dx, y) → (x, y + r·dy), markaz — burchak
        $arc = sprintf(
            '%.2F %.2F m %.2F %.2F %.2F %.2F %.2F %.2F c',
            $x + $r * $dx, $y,
            $x + $r * $dx, $y + $k * $dy, $x + $k * $dx, $y + $r * $dy, $x, $y + $r * $dy,
        );
        $pdf->path($page, $arc, null, self::GOLD, 0.8 * $weight);
        $pdf->line($page, $x + 6 * $dx, $y + 6 * $dy, $x + 30 * $dx, $y + 6 * $dy, 0.5 * $weight, self::GOLD);
        $pdf->line($page, $x + 6 * $dx, $y + 6 * $dy, $x + 6 * $dx, $y + 30 * $dy, 0.5 * $weight, self::GOLD);
        $this->diamond($pdf, $page, $x + 6 * $dx, $y + 6 * $dy, 2.6 * $weight);
    }

    /**
     * Oltin chiziq o'rtasida romb (bo'linish belgisi).
     */
    private function divider(SimplePdf $pdf, int $page, float $from, float $to, float $y, float $lineWidth): void
    {
        $mid = ($from + $to) / 2;
        $pdf->line($page, $from, $y, $mid - 9, $y, $lineWidth, self::GOLD);
        $pdf->line($page, $mid + 9, $y, $to, $y, $lineWidth, self::GOLD);
        $this->diamond($pdf, $page, $mid, $y, 3.6);
    }

    private function diamond(SimplePdf $pdf, int $page, float $x, float $y, float $r): void
    {
        $pdf->path($page, sprintf('%.2F %.2F m %.2F %.2F l %.2F %.2F l %.2F %.2F l h', $x, $y + $r, $x + $r, $y, $x, $y - $r, $x - $r, $y), self::GOLD);
    }

    /** To'rt qirrali oltin yulduz */
    private function star(SimplePdf $pdf, int $page, float $x, float $y, float $r): void
    {
        $i = $r * 0.28;
        $pdf->path($page, sprintf(
            '%.2F %.2F m %.2F %.2F l %.2F %.2F l %.2F %.2F l %.2F %.2F l %.2F %.2F l %.2F %.2F l %.2F %.2F l h',
            $x, $y + $r, $x + $i, $y + $i, $x + $r, $y, $x + $i, $y - $i, $x, $y - $r, $x - $i, $y - $i, $x - $r, $y, $x - $i, $y + $i,
        ), self::GOLD);
    }

    /** Ark (yarim doira tepali to'rtburchak) yo'li */
    private function archPath(float $x, float $bottom, float $width, float $springY, float $radius): string
    {
        $k = 0.5523 * $radius;
        $cx = $x + $width / 2;

        return sprintf(
            '%.2F %.2F m %.2F %.2F l %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F l h',
            $x, $bottom,
            $x, $springY,
            $x, $springY + $k, $cx - $k, $springY + $radius, $cx, $springY + $radius,
            $cx + $k, $springY + $radius, $x + $width, $springY + $k, $x + $width, $springY,
            $x + $width, $bottom,
        );
    }

    private function kind(): string
    {
        $kind = Translations::line(config('journal.pdf.kind'));

        return is_string($kind) && $kind !== '' ? $kind : 'Ilmiy-nazariy va amaliy jurnal';
    }

    private function slogan(): string
    {
        return (string) config('journal.pdf.slogan', 'Inson – jamiyat – taraqqiyot');
    }

    private function city(): string
    {
        return (string) config('journal.pdf.city', 'Toshkent');
    }

    private function sentence(?string $text): string
    {
        $text = trim((string) $text);

        return $text === '' ? '' : ' '.rtrim($text, '.').'.';
    }

    /** public/ ichidagi fayl (yo'q bo'lsa — null) */
    private function asset(string $path): ?string
    {
        $file = MediaUrl::publicAsset($path) !== null ? public_path($path) : null;
        $contents = $file !== null ? @file_get_contents($file) : false;

        return is_string($contents) && $contents !== '' ? $contents : null;
    }
}
