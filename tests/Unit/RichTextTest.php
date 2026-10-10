<?php

namespace Tests\Unit;

use App\Support\Html\RichText;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RichTextTest extends TestCase
{
    public function test_allowed_formatting_is_kept(): void
    {
        $html = '<h2 style="text-align: center">Sarlavha</h2><p>Oddiy <strong>qalin</strong>, <em>kursiv</em>, <u>tagi</u>, <s>chizilgan</s></p>'
            .'<ul><li>bir</li></ul><ol><li>ikki</li></ol><blockquote><p>Iqtibos</p></blockquote><hr>'
            .'<p style="text-align: justify"><a href="https://insonvajamiyat.uz/about">havola</a></p>';

        $this->assertSame(
            '<h2 style="text-align: center">Sarlavha</h2><p>Oddiy <strong>qalin</strong>, <em>kursiv</em>, <u>tagi</u>, <s>chizilgan</s></p>'
            .'<ul><li>bir</li></ul><ol><li>ikki</li></ol><blockquote><p>Iqtibos</p></blockquote><hr>'
            .'<p style="text-align: justify"><a href="https://insonvajamiyat.uz/about" target="_blank" rel="noopener noreferrer nofollow">havola</a></p>',
            RichText::sanitize($html),
        );
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function attacks(): array
    {
        return [
            'script' => ['<p>Salom</p><script>alert(1)</script>', '<p>Salom</p>'],
            'onerror img' => ['<p>x<img src=x onerror=alert(1)></p>', '<p>x</p>'],
            'event attribute' => ['<p onclick="alert(1)" class="x" id="y">Bos</p>', '<p>Bos</p>'],
            'javascript href' => ['<p><a href="javascript:alert(1)">bos</a></p>', '<p>bos</p>'],
            'obfuscated href' => ['<p><a href="java&#x09;script:alert(1)">bos</a></p>', '<p>bos</p>'],
            'data href' => ['<p><a href="data:text/html;base64,PHNjcmlwdD4=">bos</a></p>', '<p>bos</p>'],
            'protocol-relative' => ['<p><a href="//evil.example">bos</a></p>', '<p>bos</p>'],
            'iframe' => ['<iframe src="https://evil.example"></iframe><p>ok</p>', '<p>ok</p>'],
            'style tag' => ['<style>body{display:none}</style><p>ok</p>', '<p>ok</p>'],
            'css expression' => ['<p style="background:url(javascript:alert(1)); text-align: center">ok</p>', '<p style="text-align: center">ok</p>'],
            'unknown tag unwrapped' => ['<div><span class="big">matn</span></div>', 'matn'],
            'comment' => ['<p>a<!-- <script>alert(1)</script> -->b</p>', '<p>ab</p>'],
            'svg' => ['<svg><script>alert(1)</script></svg><p>ok</p>', '<p>ok</p>'],
            'form' => ['<form action="https://evil"><input name="p"></form><p>ok</p>', '<p>ok</p>'],
        ];
    }

    #[DataProvider('attacks')]
    public function test_dangerous_markup_is_removed(string $input, string $expected): void
    {
        $this->assertSame($expected, RichText::sanitize($input));
    }

    public function test_plain_text_becomes_escaped_paragraphs(): void
    {
        $this->assertSame(
            '<p>Birinchi: 2 &lt; 3 &amp; 4 &gt; 1.<br>Davomi.</p><p>Ikkinchi.</p>',
            RichText::toHtml("Birinchi: 2 < 3 & 4 > 1.\r\nDavomi.\r\n\r\nIkkinchi."),
        );
        $this->assertSame('', RichText::toHtml("  \n "));
    }

    public function test_plain_and_without_lead(): void
    {
        $html = '<p>Qisqa  mazmun.</p><h2>Batafsil</h2><p>Matn &amp; davomi</p>';

        $this->assertSame('Qisqa mazmun. Batafsil Matn & davomi', RichText::plain($html));
        $this->assertSame('<h2>Batafsil</h2><p>Matn &amp; davomi</p>', RichText::withoutLead($html, 'Qisqa mazmun.'));
        $this->assertSame($html, RichText::withoutLead($html, 'Boshqa matn'));
    }
}
