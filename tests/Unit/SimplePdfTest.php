<?php

namespace Tests\Unit;

use App\Support\Pdf\DocumentPdf;
use App\Support\Pdf\SimplePdf;
use App\Support\Pdf\TrueTypeFont;
use Tests\TestCase;

/**
 * PDF yozuvchi: kirill va o'zbek lotin belgilari joylangan shrift bilan chiqadi.
 */
class SimplePdfTest extends TestCase
{
    public function test_unicode_text_is_written_with_embedded_subset_font(): void
    {
        $pdf = new SimplePdf;
        $page = $pdf->addPage();
        $pdf->text($page, 50, 700, 'Oʻzbekiston — Ўзбекистон, Ғафур Ғулом, «Қўқон» № 7', 12, 'F5');

        $output = $pdf->output();

        $this->assertStringStartsWith('%PDF-1.4', $output);
        $this->assertStringContainsString('/Encoding /Identity-H', $output);
        $this->assertStringContainsString('/FontFile2', $output);
        $this->assertStringContainsString('+LiberationSerif', $output);
        // Faqat bitta shrift (F5) ishlatilgan — boshqalari joylanmaydi
        $this->assertSame(1, substr_count($output, '/Subtype /Type0'));
        // Kichraytirilgan shrift: butun PDF 60 KB dan kichik (to'liq shrift ~400 KB)
        $this->assertLessThan(60_000, strlen($output));

        // ToUnicode jadvalida kirill harfi (Ў = U+040E) — nusxa olish va qidiruv ishlaydi
        preg_match_all('/stream\n(.*?)\nendstream/s', $output, $streams);
        $decoded = implode("\n", array_map(fn (string $s): string => (string) @gzuncompress($s), $streams[1]));
        $this->assertStringContainsString('<040E>', $decoded);
        $this->assertStringContainsString('<02BB>', $decoded);
    }

    public function test_width_uses_real_glyph_metrics(): void
    {
        $font = TrueTypeFont::load(resource_path('fonts/liberation/LiberationSans-Regular.ttf'));

        // Liberation Sans = Helvetica o'lchamlari: "a" 556, "i" 222 (1/1000 em)
        $this->assertSame(556.0, round($font->width($font->glyph(ord('a')))));
        $this->assertSame(222.0, round($font->width($font->glyph(ord('i')))));
        $this->assertTrue($font->hasGlyph(0x040E));
        $this->assertTrue($font->hasGlyph(0x02BB));
        $this->assertEqualsWithDelta(5.56 + 2.22, SimplePdf::width('ai', 10), 0.01);
    }

    public function test_document_pdf_breaks_long_text_into_pages(): void
    {
        $body = implode("\n", array_fill(0, 80, str_repeat('Ушбу мақолада анъанавий турар жойлар таҳлил қилинган. ', 4)));
        $output = DocumentPdf::render('Таржима', $body, 'Oʻzbekcha → Ruscha · v1');

        $this->assertGreaterThan(3, substr_count($output, '/Type /Page '));
        $this->assertStringContainsString('/Count ', $output);
    }
}
