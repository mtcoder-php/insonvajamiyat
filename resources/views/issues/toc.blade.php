{{-- Jurnal soni mundarijasi — chop etish / "PDF sifatida saqlash" uchun sahifa (A4). --}}
@php
    /** @var \App\Models\JournalIssue $issue */
    $title = $issue->getTranslation('title', app()->getLocale(), false);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mundarija — {{ $issue->label }}</title>
    <style>
        @page { size: A4; margin: 20mm 18mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #eef2f8;
            color: #001e3c;
            font-family: "Times New Roman", "Noto Serif", Georgia, serif;
            font-size: 12.5pt;
            line-height: 1.45;
        }
        .sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 24px auto;
            padding: 20mm 18mm;
            background: #fff;
            box-shadow: 0 20px 50px -30px rgba(0, 36, 66, .5);
        }
        header { text-align: center; border-bottom: 2px solid #001e3c; padding-bottom: 10px; margin-bottom: 18px; }
        header .journal { font-size: 18pt; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
        header .subtitle { font-style: italic; color: #2b4a6b; }
        header .meta { margin-top: 6px; font-size: 10.5pt; color: #2b4a6b; }
        h1 { text-align: center; font-size: 16pt; letter-spacing: .2em; margin: 18px 0 4px; }
        .issue { text-align: center; font-size: 12pt; margin-bottom: 18px; }
        h2 { font-size: 12.5pt; text-transform: uppercase; letter-spacing: .05em; margin: 18px 0 6px; color: #0b3a6b; }
        ol { list-style: none; padding: 0; margin: 0; }
        li { display: flex; align-items: flex-end; gap: 6px; margin: 0 0 10px; break-inside: avoid; }
        .entry { flex: 1 1 auto; min-width: 0; }
        .entry .authors { font-weight: 700; }
        .entry .title { display: block; }
        .dots { flex: 1 0 24px; border-bottom: 1.5px dotted #6b7f96; margin-bottom: 5px; }
        .pages { flex: 0 0 auto; font-variant-numeric: tabular-nums; }
        .empty { text-align: center; color: #6b7f96; font-style: italic; padding: 40px 0; }
        .toolbar {
            position: sticky; top: 0; z-index: 1;
            display: flex; justify-content: center; gap: 10px;
            padding: 10px; background: #001e3c;
            font-family: system-ui, sans-serif;
        }
        .toolbar button, .toolbar a {
            border: 0; border-radius: 8px; padding: 8px 16px; cursor: pointer;
            font: 600 14px system-ui, sans-serif; text-decoration: none;
            background: #006cf6; color: #fff; transition: transform .15s, background .15s;
        }
        .toolbar a { background: rgba(255, 255, 255, .12); }
        .toolbar button:hover, .toolbar a:hover { transform: translateY(-1px); background: #1a82f7; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { width: auto; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Chop etish / PDF sifatida saqlash</button>
        <a href="{{ route('admin.issues.show', $issue->slug) }}">Songa qaytish</a>
    </div>

    <main class="sheet">
        <header>
            <div class="journal">{{ $journal['name'] ?? 'Inson va Jamiyat' }}</div>
            <div class="subtitle">{{ $journal['subtitle'] ?? '' }}</div>
            <div class="meta">
                @if (! empty($journal['issn'])) ISSN {{ $journal['issn'] }} @endif
                @if (! empty($journal['eissn'])) · e-ISSN {{ $journal['eissn'] }} @endif
            </div>
        </header>

        <h1>MUNDARIJA</h1>
        <div class="issue">
            {{ $issue->year }}-yil, {{ $issue->number }}-son
            @if ($issue->volume) · {{ $issue->volume }}-jild @endif
            @if (is_string($title) && $title !== '') <br><em>{{ $title }}</em> @endif
        </div>

        @forelse ($groups as $group)
            @if ($group['section'])
                <h2>{{ $group['section'] }}</h2>
            @endif
            <ol>
                @foreach ($group['articles'] as $item)
                    <li>
                        <span class="entry">
                            @if ($item['authors'] !== '')
                                <span class="authors">{{ $item['authors'] }}</span>
                            @endif
                            <span class="title">{{ $item['title'] }}</span>
                        </span>
                        <span class="dots"></span>
                        <span class="pages">{{ $item['pages'] ?? '—' }}</span>
                    </li>
                @endforeach
            </ol>
        @empty
            <p class="empty">Songa hali maqola biriktirilmagan.</p>
        @endforelse
    </main>
</body>
</html>
