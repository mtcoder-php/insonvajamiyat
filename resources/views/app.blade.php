@php
    // Ommaviy sayt, auth va muallif kabineti (shu jumladan muallifning shaxsiy sozlamalari)
    // faqat yorug' mavzuda — dizayn shunga mo'ljallangan; qorong'i mavzu faqat admin panelda.
    // resources/js/composables/useAppearance.ts → isLightOnlyComponent() bilan bir xil.
    $component = $page['component'];
    $isStaff = (bool) data_get($page, 'props.auth.isStaff', false);
    $lightOnly = str_starts_with($component, 'web/')
        || str_starts_with($component, 'auth/')
        || str_starts_with($component, 'cabinet/')
        || str_starts_with($component, 'errors/')
        || (! $isStaff && str_starts_with($component, 'settings/'));

    // SEO: title, description, Open Graph, canonical, Google Scholar (citation_*), JSON-LD
    $seo = app(\App\Support\Seo\SeoMeta::class)->resolve($component);
@endphp
<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    @class(['dark' => ! $lightOnly && ($appearance ?? 'system') == 'dark'])
    @if ($lightOnly) data-theme="light-only" @endif
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                if (document.documentElement.dataset.theme === 'light-only') {
                    return;
                }

                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: #f0f6fc;
            }

            html.dark {
                background-color: #07162a;
            }
        </style>

        <meta name="theme-color" content="#001e3c">

        {{-- data-inertia="description": sahifadagi <Head><meta head-key="description"> shu tegni almashtiradi (dublikat bo'lmaydi) --}}
        <meta name="description" content="{{ $seo['description'] }}" data-inertia="description">
        @if ($seo['robots'])
            <meta name="robots" content="{{ $seo['robots'] }}">
        @else
            <link rel="canonical" href="{{ $seo['canonical'] }}">
        @endif
        <meta property="og:site_name" content="{{ $seo['siteName'] }}">
        <meta property="og:locale" content="{{ ['uz' => 'uz_UZ', 'ru' => 'ru_RU', 'en' => 'en_US'][app()->getLocale()] ?? 'uz_UZ' }}">
        <meta property="og:type" content="{{ $seo['type'] }}">
        <meta property="og:title" content="{{ $seo['title'] }}">
        <meta property="og:description" content="{{ $seo['description'] }}">
        <meta property="og:url" content="{{ $seo['url'] }}">
        @if ($seo['image'])
            <meta property="og:image" content="{{ $seo['image'] }}">
            <meta name="twitter:card" content="summary_large_image">
        @endif
        @foreach ($seo['meta'] as [$name, $content])
            <meta name="{{ $name }}" content="{{ $content }}">
        @endforeach
        @if ($seo['jsonLd'])
            <script type="application/ld+json">{!! $seo['jsonLd'] !!}</script>
        @endif

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon-96x96.png" type="image/png" sizes="96x96">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        {{-- Sahifa sarlavhasi: Inertia klientda <Head title> bilan almashtiradi (data-inertia'siz title o'chiriladi) --}}
        <title>{{ $seo['title'] }}</title>
        <x-inertia::head />
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
