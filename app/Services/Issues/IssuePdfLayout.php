<?php

namespace App\Services\Issues;

use App\Models\JournalIssue;
use App\Services\Content\EditorialBoardService;
use App\Support\Pdf\SimplePdf;
use App\Support\Translations;

/**
 * Son PDF ining "jurnal tomonidan" chiziladigan qismlari:
 *  - 2-bet — jurnal ma'lumotlari (son uchun alohida sahifa yuklanmagan bo'lsa, shu standart sahifa);
 *  - maqola betlariga bet raqami va kolontitul (qpdf --overlay uchun shaffof sahifalar).
 *
 * Dizayn vaqtinchalik: tahririyat taqdim etgan maket asosida almashtiriladi.
 */
class IssuePdfLayout
{
    /** To'q ko'k (navy-950) va oltin (gold-500) */
    private const NAVY = [0.0, 0.118, 0.235];

    private const GOLD = [0.769, 0.604, 0.271];

    private const MUTED = [0.35, 0.42, 0.5];

    public function __construct(private readonly EditorialBoardService $board) {}

    /**
     * 2-bet: jurnal nomi, son, ISSN, muassis/tahririyat manzili va tahrir hay'ati.
     */
    public function infoPage(JournalIssue $issue): string
    {
        $pdf = new SimplePdf;
        $page = $pdf->addPage();
        $w = SimplePdf::A4_WIDTH;
        $h = SimplePdf::A4_HEIGHT;
        $margin = 56.0;

        // Yuqori bant
        $pdf->rect($page, 0, $h - 150, $w, 150, self::NAVY);
        $pdf->rect($page, 0, $h - 154, $w, 4, self::GOLD);
        $pdf->text($page, $w / 2, $h - 70, mb_strtoupper((string) config('journal.name')), 26, 'F4', 'center', [1, 1, 1]);
        $pdf->text($page, $w / 2, $h - 94, (string) config('journal.subtitle'), 12, 'F3', 'center', [0.94, 0.81, 0.49]);
        $pdf->text($page, $w / 2, $h - 124, $this->issueLine($issue), 11, 'F2', 'center', [1, 1, 1]);

        $y = $h - 200;
        $description = Translations::line(config('journal.description'));

        if (is_string($description) && $description !== '') {
            $y = $pdf->paragraph($page, $margin, $y, $w - 2 * $margin, $description, 11, 'F3', 1.45, self::NAVY, 'center') - 14;
        }

        // Rekvizitlar
        $facts = array_filter([
            'ISSN (print)' => config('journal.issn'),
            'ISSN (online)' => config('journal.eissn'),
            'DOI' => $issue->doi ?: null,
            'Davriyligi' => Translations::line(config('journal.frequency')),
            'Manzil' => Translations::line(config('journal.contact.address')),
            'Elektron pochta' => config('journal.contact.email'),
            'Telefon' => config('journal.contact.phone'),
            'Veb-sayt' => preg_replace('~^https?://~', '', (string) config('app.url')),
        ], fn (mixed $v): bool => is_string($v) && trim($v) !== '');

        $pdf->line($page, $margin, $y, $w - $margin, $y, 0.6, self::GOLD);
        $y -= 22;

        foreach ($facts as $label => $value) {
            $pdf->text($page, $margin, $y, $label.':', 9.5, 'F2', 'left', self::MUTED);
            $pdf->text($page, $margin + 120, $y, (string) $value, 9.5, 'F1', 'left', self::NAVY);
            $y -= 16;
        }

        // Tahrir hay'ati
        $groups = $this->board->public();

        if ($groups !== []) {
            $y -= 10;
            $pdf->line($page, $margin, $y, $w - $margin, $y, 0.6, self::GOLD);
            $y -= 24;
            $pdf->text($page, $margin, $y, "TAHRIR HAY'ATI", 11, 'F2', 'left', self::NAVY);
            $y -= 20;

            foreach ($groups as $group) {
                if ($y < 90) {
                    break;
                }

                $pdf->text($page, $margin, $y, (string) $group['label'], 9.5, 'F2', 'left', self::GOLD);
                $y -= 15;

                foreach ($group['members'] as $member) {
                    if ($y < 90) {
                        break;
                    }

                    $details = implode(', ', array_filter([$member['degree'] ?? null, $member['organization'] ?? null]));
                    $pdf->text($page, $margin + 10, $y, (string) $member['name'], 9.5, 'F2', 'left', self::NAVY);

                    if ($details !== '') {
                        $y = $pdf->paragraph($page, $margin + 170, $y, $w - 2 * $margin - 170, $details, 8.5, 'F1', 1.35, self::MUTED);
                    } else {
                        $y -= 13;
                    }

                    $y -= 3;
                }

                $y -= 6;
            }
        }

        $pdf->line($page, $margin, 64, $w - $margin, 64, 0.6, self::GOLD);
        $pdf->text($page, $w / 2, 46, '© '.$issue->year.' '.config('journal.name').'. Barcha huquqlar himoyalangan.', 8, 'F1', 'center', self::MUTED);

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
     * Birlashtirilgan fayl ustiga bosiladigan shaffof sahifalar.
     *
     * @param  array<int, array{0: float, 1: float}>  $sizes  har bir sahifa o'lchami (pt)
     * @param  array<int, 'none'|'number'|'full'>  $modes  sahifa → nima bosiladi
     */
    public function overlay(JournalIssue $issue, array $sizes, array $modes): string
    {
        $pdf = new SimplePdf;
        $header = (bool) config('journal.pdf.stamp_header', true);
        $journal = mb_strtoupper((string) config('journal.name'));
        $issueLine = $this->issueLine($issue);

        foreach ($sizes as $index => [$width, $height]) {
            $page = $pdf->addPage($width, $height);
            $mode = $modes[$index] ?? 'none';

            if ($mode === 'none') {
                continue;
            }

            $number = (string) ($index + 1);
            $margin = max(28.0, $width * 0.07);

            if ($mode === 'full' && $header) {
                $top = $height - 26;
                // Kitobdagidek ko'zgu: juft (chap) betda jurnal nomi chapda, toq (o'ng) betda — o'ngda
                $even = ($index + 1) % 2 === 0;
                [$left, $leftFont, $right, $rightFont] = $even
                    ? [$journal, 'F2', $issueLine, 'F3']
                    : [$issueLine, 'F3', $journal, 'F2'];
                $pdf->text($page, $margin, $top, $left, 7.5, $leftFont, 'left', self::NAVY);
                $pdf->text($page, $width - $margin, $top, $right, 7.5, $rightFont, 'right', self::NAVY);
                $pdf->line($page, $margin, $top - 6, $width - $margin, $top - 6, 0.5, self::GOLD);
            }

            $pdf->text($page, $width / 2, 22, $number, 9, 'F2', 'center', self::NAVY);
        }

        return $pdf->output();
    }

    /** "2026-yil, 3-son (12-jild)" */
    public function issueLine(JournalIssue $issue): string
    {
        return $issue->year.'-yil, '.$issue->number.'-son'.($issue->volume ? ' ('.$issue->volume.'-jild)' : '');
    }
}
