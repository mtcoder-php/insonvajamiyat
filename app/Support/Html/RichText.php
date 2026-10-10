<?php

namespace App\Support\Html;

use App\Services\Content\ContentImageService;
use App\Support\MediaUrl;
use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Matn muharriri (admin → Yangiliklar / Tadbirlar) HTML'ini xavfsiz holatga keltirish.
 *
 * Faqat ruxsat etilgan teglar qoladi (sarlavha, xatboshi, ro'yxat, iqtibos, havola, qalin/kursiv …),
 * atributlardan — faqat havola manzili va matnni tekislash. Rasm (<img>) faqat shu saytga muharrir
 * orqali yuklangan bo'lsa qoladi (content-images/…), begona manzildagi rasmlar olib tashlanadi. Skript, iframe, forma, on* hodisalar,
 * javascript: havolalar va boshqa hamma narsa olib tashlanadi. Saqlashda ham, saytda chiqarishda
 * ham shu tozalagichdan o'tadi. Eski (teglarsiz) matnlar xatboshilarga aylantiriladi.
 */
final class RichText
{
    /** Ruxsat etilgan teglar → chiqishdagi nomi (h1 → h2, b → strong …) */
    private const ALLOWED = [
        'p' => 'p', 'br' => 'br', 'hr' => 'hr',
        'h1' => 'h2', 'h2' => 'h2', 'h3' => 'h3', 'h4' => 'h3',
        'strong' => 'strong', 'b' => 'strong', 'em' => 'em', 'i' => 'em',
        'u' => 'u', 's' => 's', 'del' => 's', 'strike' => 's',
        'ul' => 'ul', 'ol' => 'ol', 'li' => 'li', 'blockquote' => 'blockquote', 'a' => 'a',
    ];

    /** Ichidagi matni bilan butunlay o'chiriladigan teglar */
    private const DROP = [
        'script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed', 'applet', 'form', 'input',
        'button', 'textarea', 'select', 'option', 'svg', 'math', 'template', 'noscript', 'head', 'title',
        'meta', 'link', 'base', 'video', 'audio', 'source', 'track', 'canvas', 'picture',
    ];

    /** text-align ruxsat etilgan bloklar */
    private const ALIGNABLE = ['p', 'h2', 'h3'];

    private const ALIGNMENTS = ['left', 'center', 'right', 'justify'];

    /**
     * Saqlash va chiqarish uchun: HTML bo'lsa — tozalanadi, oddiy matn bo'lsa — xatboshilarga aylanadi.
     */
    public static function toHtml(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        return self::isHtml($value) ? self::sanitize($value) : self::fromPlainText($value);
    }

    public static function isHtml(string $value): bool
    {
        return preg_match('/<\s*\/?\s*[a-z][a-z0-9]*[\s>\/]/i', $value) === 1;
    }

    /**
     * Oddiy matn → <p>…</p> (bo'sh qator — yangi xatboshi, bitta qator — <br>).
     */
    public static function fromPlainText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", trim($text));
        $paragraphs = preg_split('/\n[^\S\n]*\n/u', $text) ?: [];
        $html = '';

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);

            if ($paragraph !== '') {
                $html .= '<p>'.str_replace("\n", '<br>', e($paragraph)).'</p>';
            }
        }

        return $html;
    }

    /**
     * HTML → oddiy matn (SEO tavsifi, qidiruv, taqqoslash uchun).
     */
    public static function plain(?string $value): string
    {
        $value = (string) $value;
        $value = (string) preg_replace('/<\s*(br|\/p|\/h[1-6]|\/li|\/blockquote|hr)[^>]*>/i', ' ', $value);

        return trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    /**
     * Matn birinchi xatboshisi qisqa mazmun bilan bir xil bo'lsa — uni olib tashlaydi
     * (saytda qisqa mazmun alohida, yirik shriftda chiqadi).
     */
    public static function withoutLead(string $html, ?string $lead): string
    {
        $lead = self::plain($lead);

        if ($lead === '' || preg_match('/^\s*<p[^>]*>(.*?)<\/p>/is', $html, $match) !== 1) {
            return $html;
        }

        return self::plain($match[1]) === $lead ? ltrim(substr($html, strlen($match[0]))) : $html;
    }

    public static function sanitize(string $html): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="rich-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        // LIBXML_HTML_NOIMPLIED: <html>/<body> qo'shilmaydi — ildiz element bizning <div>
        $root = $document->documentElement;

        if (! $root instanceof DOMElement || $root->getAttribute('id') !== 'rich-root') {
            return '';
        }

        self::cleanChildren($root, $document);

        $output = '';

        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        // Bo'sh xatboshilarni olib tashlash
        $output = (string) preg_replace('/<p>(\s|&nbsp;|<br>)*<\/p>/u', '', $output);

        return trim($output);
    }

    private static function cleanChildren(DOMNode $parent, DOMDocument $document): void
    {
        // Ro'yxatni oldindan nusxalaymiz — tsikl ichida daraxt o'zgaradi
        $children = iterator_to_array($parent->childNodes);

        foreach ($children as $node) {
            if ($node->nodeType === XML_TEXT_NODE) {
                continue;
            }

            if (! $node instanceof DOMElement) {
                // Izohlar, CDATA, processing instruction …
                $parent->removeChild($node);

                continue;
            }

            $tag = strtolower($node->tagName);

            if (in_array($tag, self::DROP, true)) {
                $parent->removeChild($node);

                continue;
            }

            if ($tag === 'img') {
                $src = self::safeImageSrc($node->getAttribute('src'));

                if ($src === null) {
                    $parent->removeChild($node);

                    continue;
                }

                $image = $document->createElement('img');
                $image->setAttribute('src', $src);
                $image->setAttribute('alt', mb_substr(trim($node->getAttribute('alt')), 0, 200));
                $image->setAttribute('loading', 'lazy');
                $image->setAttribute('decoding', 'async');
                $parent->replaceChild($image, $node);

                continue;
            }

            self::cleanChildren($node, $document);

            if (! isset(self::ALLOWED[$tag])) {
                self::unwrap($node);

                continue;
            }

            $target = self::ALLOWED[$tag];
            $clean = $document->createElement($target);

            if ($target === 'a') {
                $href = self::safeHref($node->getAttribute('href'));

                if ($href === null) {
                    self::unwrap($node);

                    continue;
                }

                $clean->setAttribute('href', $href);

                if (preg_match('/^https?:/i', $href) === 1) {
                    $clean->setAttribute('target', '_blank');
                    $clean->setAttribute('rel', 'noopener noreferrer nofollow');
                }
            }

            if (in_array($target, self::ALIGNABLE, true)) {
                $align = self::alignment($node->getAttribute('style'));

                if ($align !== null) {
                    $clean->setAttribute('style', 'text-align: '.$align);
                }
            }

            while ($node->firstChild !== null) {
                $clean->appendChild($node->firstChild);
            }

            $parent->replaceChild($clean, $node);
        }
    }

    private static function unwrap(DOMElement $node): void
    {
        $parent = $node->parentNode;

        if ($parent === null) {
            return;
        }

        while ($node->firstChild !== null) {
            $parent->insertBefore($node->firstChild, $node);
        }

        $parent->removeChild($node);
    }

    private static function safeHref(string $href): ?string
    {
        // Boshqaruv belgilari va bo'shliqlar orqali yashirilgan "java\tscript:" ni ham ushlaydi
        $href = trim((string) preg_replace('/[\x00-\x20\x7F]+/', '', html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        if ($href === '' || mb_strlen($href) > 2000) {
            return null;
        }

        return preg_match('~^(https?://|mailto:|tel:|/(?!/)|#)~i', $href) === 1 ? $href : null;
    }

    /**
     * Faqat muharrir orqali shu saytga yuklangan rasm: {storage URL}/content-images/YYYY/MM/nom.ext
     */
    private static function safeImageSrc(string $src): ?string
    {
        $src = trim(html_entity_decode($src, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $pattern = '~^(?:'.preg_quote(rtrim(MediaUrl::from(ContentImageService::DIR) ?? '', '/'), '~').'|/storage/'.ContentImageService::DIR.')'
            .'/\d{4}/\d{2}/[a-z0-9]{20}\.(?:webp|png|jpe?g)$~';

        return preg_match($pattern, $src) === 1 ? $src : null;
    }

    private static function alignment(string $style): ?string
    {
        if (preg_match('/text-align\s*:\s*([a-z]+)/i', $style, $match) !== 1) {
            return null;
        }

        $value = strtolower($match[1]);

        return in_array($value, self::ALIGNMENTS, true) && $value !== 'left' ? $value : null;
    }
}
