<?php

namespace App\Services\Issues;

use App\Models\JournalIssue;
use App\Services\Content\EditorialBoardService;
use App\Support\Pdf\SimplePdf;
use App\Support\Translations;

/**
 * Son PDF ining jurnal tomonidan chiziladigan qismlari (tahririyat maketi asosida):
 *
 *  - ramka va kolontitul — har bir ichki betda: oltin ramka, tepada to'q ko'k bant
 *    ("INSON VA JAMIYAT" | bo'lim nomi), pastda oltin chiziq, shior va bet raqami;
 *  - 2-bet "Jurnal haqida" — tavsif, yo'nalish kartalari, "Nashr ma'lumotlari" bloki, tahrir hay'ati;
 *  - mundarija — ruknlar bo'yicha, nuqtali chiziq bilan bet raqamlari (son oxirida).
 *
 * Kontent betlari faqat mazmunni chizadi; ramka/kolontitul overlay() orqali bir xil qo'yiladi.
 * Maketchi maqola PDF ini chetlarida bo'sh joy bilan tayyorlaydi (MARGINS).
 */
class IssuePdfLayout
{
    public const NAVY = [0.031, 0.118, 0.235];

    public const GOLD = [0.788, 0.635, 0.302];

    private const INK = [0.13, 0.17, 0.24];

    private const MUTED = [0.42, 0.47, 0.55];

    private const CARD = [0.953, 0.965, 0.984];

    private const CREAM = [0.973, 0.953, 0.914];

    /** Ramka, bant va pastki chiziq o'rni (pt, sahifa chetidan) */
    private const FRAME = 22.0;

    private const BAND_INSET = 36.0;

    private const BAND_TOP = 38.0;

    private const BAND_HEIGHT = 26.0;

    private const FOOTER_LINE = 58.0;

    /** Kontent maydoni (A4 da): chap/o'ng, tepa, past — maketchi uchun ham shu */
    public const MARGINS = ['side' => 56.0, 'top' => 96.0, 'bottom' => 78.0];

    public function __construct(private readonly EditorialBoardService $board) {}

    /**
     * 2-bet: "Jurnal haqida" (faqat mazmun — ramka overlay bilan qo'yiladi).
     */
    public function infoPage(JournalIssue $issue): string
    {
        $pdf = new SimplePdf;
        $page = $pdf->addPage();
        $w = SimplePdf::A4_WIDTH;
        $h = SimplePdf::A4_HEIGHT;
        $x = self::MARGINS['side'];
        $width = $w - 2 * $x;
        $y = $h - self::MARGINS['top'] - 6;

        $y = $this->heading($pdf, $page, $x, $y, 'JURNAL', 'Jurnal haqida');

        $name = (string) config('journal.name');
        $description = Translations::line(config('journal.description'));
        $intro = '«'.$name.'» — inson, jamiyat, ta‘lim, madaniyat, iqtisodiyot, ijtimoiy taraqqiyot va innovatsiyalar bilan bog‘liq dolzarb masalalarni yorituvchi ilmiy-nazariy va amaliy jurnal.'
            .(is_string($description) && $description !== '' ? ' '.$description.'.' : '');
        $y = $pdf->paragraph($page, $x, $y, $width, $intro, 10.5, 'F5', 1.5, self::INK, 'justify') - 18;

        // Yo'nalish kartalari
        $cards = [
            ['01', 'ILMIY IZLANISH', 'Tadqiqotlar, nazariy qarashlar va ilmiy natijalar.'],
            ['02', 'JAMIYAT VA MADANIYAT', 'Ijtimoiy jarayonlar, ma‘naviyat va madaniy meros.'],
            ['03', 'TARAQQIYOT', 'Ta‘lim, iqtisodiyot, innovatsiya va zamonaviy yechimlar.'],
        ];
        $gap = 12.0;
        $cardW = ($width - 2 * $gap) / 3;
        $cardH = 74.0;

        foreach ($cards as $i => [$num, $title, $text]) {
            $cx = $x + $i * ($cardW + $gap);
            $pdf->roundRect($page, $cx, $y - $cardH, $cardW, $cardH, 8, self::CARD);
            $pdf->roundRect($page, $cx + 10, $y - 30, 20, 20, 10, self::GOLD);
            $pdf->text($page, $cx + 20, $y - 23.5, $num, 7.5, 'F2', 'center', self::NAVY);
            $pdf->paragraph($page, $cx + 36, $y - 23, $cardW - 44, $title, 7.5, 'F2', 1.3, self::NAVY);
            $pdf->paragraph($page, $cx + 10, $y - 44, $cardW - 20, $text, 7.5, 'F1', 1.4, self::MUTED);
        }

        $y -= $cardH + 20;

        // Nashr ma'lumotlari (to'q ko'k blok)
        $rows = array_filter([
            'Nashr turi' => 'Ilmiy-nazariy va amaliy jurnal',
            'Nashr soni' => '№ '.$issue->number.'  |  '.$issue->year.'-yil'.($issue->volume ? '  |  '.$issue->volume.'-jild' : ''),
            'ISSN (bosma)' => config('journal.issn'),
            'ISSN (onlayn)' => config('journal.eissn'),
            'DOI' => $issue->doi ?: config('journal.doi_prefix'),
            'Davriyligi' => Translations::line(config('journal.frequency')),
            'Manzil' => Translations::line(config('journal.contact.address')),
            'Aloqa' => implode('  ·  ', array_filter([config('journal.contact.email'), config('journal.contact.phone')], 'is_string')),
            'Veb-sayt' => preg_replace('~^https?://~', '', rtrim((string) config('app.url'), '/')),
        ], fn (mixed $v): bool => is_string($v) && trim($v) !== '');

        $boxH = 44 + count($rows) * 15.5;
        $pdf->roundRect($page, $x, $y - $boxH, $width, $boxH, 10, self::NAVY);
        $pdf->text($page, $x + 18, $y - 24, 'Nashr ma‘lumotlari', 11, 'F2', 'left', self::GOLD);
        $ry = $y - 44;

        foreach ($rows as $label => $value) {
            $pdf->text($page, $x + 18, $ry, $label, 8, 'F1', 'left', [0.78, 0.82, 0.88]);
            $pdf->text($page, $x + 130, $ry, (string) $value, 8, 'F1', 'left', [1, 1, 1]);
            $ry -= 15.5;
        }

        $y -= $boxH + 22;

        // Tahrir hay'ati (sig'gancha)
        $groups = $this->board->public();
        $bottom = self::MARGINS['bottom'] + 8;

        if ($groups !== [] && $y > $bottom + 60) {
            $pdf->text($page, $x, $y, 'TAHRIR HAY‘ATI', 9, 'F2', 'left', self::NAVY, 0.6);
            $pdf->line($page, $x, $y - 6, $x + 90, $y - 6, 1.2, self::GOLD);
            $y -= 22;

            foreach ($groups as $group) {
                if ($y < $bottom + 24) {
                    break;
                }

                $pdf->text($page, $x, $y, mb_strtoupper((string) $group['label']), 7, 'F2', 'left', self::GOLD, 0.4);
                $y -= 13;

                foreach ($group['members'] as $member) {
                    if ($y < $bottom + 12) {
                        break 2;
                    }

                    $pdf->text($page, $x, $y, (string) $member['name'], 8.5, 'F2', 'left', self::NAVY);
                    $details = implode(', ', array_filter([$member['degree'] ?? null, $member['organization'] ?? null], 'is_string'));

                    if ($details !== '') {
                        $y = $pdf->paragraph($page, $x + 150, $y, $width - 150, $details, 7.5, 'F1', 1.35, self::MUTED) + 7.5 * 1.35;
                    }

                    $y -= 13;
                }

                $y -= 5;
            }
        }

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
        $x = self::MARGINS['side'];
        $width = $w - 2 * $x;
        $numberCol = 34.0;
        $bottom = self::MARGINS['bottom'] + 10;

        $page = $pdf->addPage();
        $y = $this->heading($pdf, $page, $x, $h - self::MARGINS['top'] - 6, 'MUNDARIJA', 'Mundarija');

        foreach ($groups as $group) {
            if ($group['section'] !== null) {
                if ($y < $bottom + 60) {
                    $page = $pdf->addPage();
                    $y = $h - self::MARGINS['top'] - 6;
                }

                $y -= 4;
                $pdf->roundRect($page, $x, $y - 5, $width, 18, 4, self::CREAM);
                $pdf->text($page, $x + 8, $y, mb_strtoupper($group['section']), 8, 'F2', 'left', self::NAVY, 0.5);
                $y -= 24;
            }

            foreach ($group['articles'] as $article) {
                $titleLines = SimplePdf::wrap($article['title'], $width - $numberCol - 12, 10.5, 'F4');
                $authorLines = $article['authors'] !== '' ? SimplePdf::wrap($article['authors'], $width - $numberCol - 12, 8.5, 'F6') : [];
                $needed = count($titleLines) * 14 + count($authorLines) * 11.5 + 12;

                if ($y - $needed < $bottom) {
                    $page = $pdf->addPage();
                    $y = $h - self::MARGINS['top'] - 6;
                }

                foreach ($titleLines as $i => $line) {
                    $pdf->text($page, $x, $y, $line, 10.5, 'F4', 'left', self::NAVY);

                    if ($i === count($titleLines) - 1 && $article['pages'] !== null) {
                        // Nuqtali chiziq sarlavha oxiridan bet raqamigacha
                        $from = $x + SimplePdf::width(SimplePdf::encode($line), 10.5, 'F4') + 6;
                        $to = $x + $width - $numberCol;

                        if ($to - $from > 12) {
                            $pdf->text($page, $from, $y, str_repeat('.', (int) (($to - $from) / 3.1)), 9, 'F1', 'left', [0.72, 0.75, 0.8]);
                        }

                        $pdf->text($page, $x + $width, $y, (string) $article['pages'], 10, 'F2', 'right', self::NAVY);
                    }

                    $y -= 14;
                }

                foreach ($authorLines as $line) {
                    $pdf->text($page, $x, $y + 1, $line, 8.5, 'F6', 'left', self::MUTED);
                    $y -= 11.5;
                }

                $y -= 9;
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
     * Birlashtirilgan fayl ustiga bosiladigan shaffof sahifalar: ramka, bant (bo'lim nomi), shior, bet raqami.
     *
     * @param  array<int, array{0: float, 1: float}>  $sizes  har bir sahifa o'lchami (pt)
     * @param  array<int, string|null>  $labels  sahifa → bantdagi bo'lim nomi (null — hech narsa bosilmaydi)
     */
    public function overlay(JournalIssue $issue, array $sizes, array $labels): string
    {
        $pdf = new SimplePdf;
        $journal = mb_strtoupper((string) config('journal.name'));
        $motto = (string) config('journal.pdf.motto', '');

        foreach ($sizes as $index => [$width, $height]) {
            $page = $pdf->addPage($width, $height);
            $label = $labels[$index] ?? null;

            if ($label === null) {
                continue;
            }

            // Oltin ramka
            $pdf->strokeRect($page, self::FRAME, self::FRAME, $width - 2 * self::FRAME, $height - 2 * self::FRAME, 0.9, self::GOLD);

            // Tepada to'q ko'k bant
            $bandY = $height - self::BAND_TOP - self::BAND_HEIGHT;
            $pdf->rect($page, self::BAND_INSET, $bandY, $width - 2 * self::BAND_INSET, self::BAND_HEIGHT, self::NAVY);
            $pdf->text($page, self::BAND_INSET + 12, $bandY + 9.5, $journal, 8.5, 'F5', 'left', self::GOLD, 0.5);
            $pdf->text($page, $width - self::BAND_INSET - 12, $bandY + 9.5, mb_strtoupper($label), 6.5, 'F1', 'right', [1, 1, 1], 0.5);

            // Pastda oltin chiziq, shior va bet raqami
            $pdf->line($page, self::BAND_INSET, self::FOOTER_LINE, $width - self::BAND_INSET, self::FOOTER_LINE, 0.8, self::GOLD);

            if ($motto !== '') {
                $pdf->text($page, self::BAND_INSET + 2, self::FOOTER_LINE - 14, $motto, 7, 'F6', 'left', self::MUTED);
            }

            $pdf->text($page, $width - self::BAND_INSET - 2, self::FOOTER_LINE - 14.5, (string) ($index + 1), 9, 'F2', 'right', self::NAVY);
        }

        return $pdf->output();
    }

    /** "2026-yil, 3-son (12-jild)" */
    public function issueLine(JournalIssue $issue): string
    {
        return $issue->year.'-yil, '.$issue->number.'-son'.($issue->volume ? ' ('.$issue->volume.'-jild)' : '');
    }

    /**
     * Oltin "pill" yorliq + katta sarlavha + oltin chiziq. Keyingi y ni qaytaradi.
     */
    private function heading(SimplePdf $pdf, int $page, float $x, float $y, string $pill, string $title): float
    {
        $pillW = SimplePdf::width(SimplePdf::encode($pill), 7, 'F2') + 0.6 * strlen($pill) + 28;
        $pdf->roundRect($page, $x, $y - 14, $pillW, 18, 9, self::GOLD);
        $pdf->text($page, $x + $pillW / 2, $y - 8, $pill, 7, 'F2', 'center', self::NAVY, 0.6);

        $y -= 46;
        $pdf->text($page, $x, $y, $title, 24, 'F4', 'left', self::NAVY);
        $pdf->line($page, $x, $y - 10, $x + 118, $y - 10, 1.4, self::GOLD);

        return $y - 36;
    }
}
