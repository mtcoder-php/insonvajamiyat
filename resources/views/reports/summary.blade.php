{{-- Umumiy statistik hisobot — chop etish / "PDF sifatida saqlash" uchun sahifa (A4). --}}
@php
    /** @var \App\Support\ReportPeriod $period */
    $kpiLabels = [
        'submitted' => 'Yuborilgan maqolalar',
        'accepted' => 'Qabul qilingan',
        'rejected' => 'Rad etilgan',
        'review_days' => "O'rtacha taqriz vaqti (kun)",
        'authors' => 'Faol mualliflar',
        'views' => "Maqola ko'rishlari",
    ];
    $num = fn ($v) => $v === null ? '—' : number_format((float) $v, is_float($v) && floor($v) != $v ? 1 : 0, ',', ' ');
    $trend = fn ($t) => $t === null ? '' : (($t > 0 ? '+' : '').$t.'%');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Statistik hisobot — {{ $period->toArray()['label'] }}</title>
    <style>
        @page { size: A4; margin: 16mm 14mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0; background: #eef2f8; color: #001e3c;
            font-family: "Inter", "Segoe UI", system-ui, sans-serif; font-size: 10.5pt; line-height: 1.45;
        }
        .sheet {
            width: 210mm; min-height: 297mm; margin: 24px auto; padding: 16mm 14mm;
            background: #fff; box-shadow: 0 20px 50px -30px rgba(0, 36, 66, .5);
        }
        header { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #001e3c; padding-bottom: 10px; margin-bottom: 16px; }
        header .journal { font-family: "Times New Roman", Georgia, serif; font-size: 17pt; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        header .subtitle { font-style: italic; color: #2b4a6b; font-family: Georgia, serif; }
        header .meta { text-align: right; font-size: 9.5pt; color: #2b4a6b; }
        h1 { font-size: 15pt; margin: 0 0 4px; }
        .lead { color: #4a6380; margin: 0 0 16px; }
        h2 { font-size: 11.5pt; text-transform: uppercase; letter-spacing: .05em; margin: 20px 0 8px; color: #0b3a6b; border-bottom: 1px solid #d6e0ec; padding-bottom: 4px; }
        .kpis { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
        .kpi { border: 1px solid #d6e0ec; border-radius: 8px; padding: 8px 10px; break-inside: avoid; }
        .kpi .label { font-size: 9pt; color: #4a6380; }
        .kpi .value { font-size: 16pt; font-weight: 700; font-variant-numeric: tabular-nums; }
        .kpi .trend { font-size: 9pt; color: #4a6380; }
        .cols { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        table { width: 100%; border-collapse: collapse; font-size: 9.5pt; }
        th, td { padding: 5px 6px; border-bottom: 1px solid #e3eaf3; text-align: left; vertical-align: top; }
        th { background: #f3f6fa; font-weight: 600; color: #2b4a6b; }
        td.n, th.n { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        tr { break-inside: avoid; }
        .muted { color: #6b7f96; }
        footer { margin-top: 24px; font-size: 9pt; color: #6b7f96; display: flex; justify-content: space-between; }
        .toolbar {
            position: sticky; top: 0; z-index: 1; display: flex; justify-content: center; gap: 10px;
            padding: 10px; background: #001e3c; font-family: system-ui, sans-serif;
        }
        .toolbar button, .toolbar a {
            border: 0; border-radius: 8px; padding: 8px 16px; cursor: pointer; font: 600 14px system-ui, sans-serif;
            text-decoration: none; background: #006cf6; color: #fff; transition: transform .15s, background .15s;
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
        <a href="{{ $backUrl }}">Statistikaga qaytish</a>
    </div>

    <main class="sheet">
        <header>
            <div>
                <div class="journal">{{ $journal['name'] ?? 'Inson va Jamiyat' }}</div>
                <div class="subtitle">{{ $journal['subtitle'] ?? '' }}</div>
            </div>
            <div class="meta">
                @if (! empty($journal['issn'])) ISSN {{ $journal['issn'] }}<br>@endif
                Tuzilgan: {{ $generatedAt->format('d.m.Y H:i') }}
            </div>
        </header>

        <h1>Statistik hisobot</h1>
        <p class="lead">
            Davr: <strong>{{ $period->toArray()['label'] }}</strong> ({{ $period->days() }} kun)
            @if ($subject) · Yo'nalish: <strong>{{ $subject->name }}</strong> @endif
            · Taqqoslash: oldingi {{ $period->days() }} kunga nisbatan
        </p>

        <h2>Asosiy ko'rsatkichlar</h2>
        <div class="kpis">
            @foreach ($kpis as $kpi)
                <div class="kpi">
                    <div class="label">{{ $kpiLabels[$kpi['key']] ?? $kpi['key'] }}</div>
                    <div class="value">{{ $num($kpi['value']) }}</div>
                    <div class="trend">Oldingi davr: {{ $num($kpi['previous']) }} {{ $trend($kpi['trend']) }}</div>
                </div>
            @endforeach
        </div>

        <div class="cols">
            <section>
                <h2>Fan yo'nalishlari</h2>
                <table>
                    <thead><tr><th>Yo'nalish</th><th class="n">Maqolalar</th><th class="n">Ulush</th></tr></thead>
                    <tbody>
                        @forelse ($subjects['items'] as $item)
                            <tr>
                                <td>{{ $item['label'] }}</td>
                                <td class="n">{{ $num($item['value']) }}</td>
                                <td class="n">{{ $subjects['total'] > 0 ? round($item['value'] / $subjects['total'] * 100) : 0 }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="muted">Ma'lumot yo'q</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
            <section>
                <h2>Mamlakatlar bo'yicha mualliflar</h2>
                <table>
                    <thead><tr><th>Mamlakat</th><th class="n">Mualliflar</th></tr></thead>
                    <tbody>
                        @forelse ($countries['items'] as $item)
                            <tr><td>{{ $item['label'] }}</td><td class="n">{{ $num($item['value']) }}</td></tr>
                        @empty
                            <tr><td colspan="2" class="muted">Ma'lumot yo'q</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        </div>

        <div class="cols">
            <section>
                <h2>Daromadlar</h2>
                <table>
                    <tbody>
                        <tr><td>Click</td><td class="n">{{ $num(array_sum($revenue['click'])) }} so'm</td></tr>
                        <tr><td>Payme</td><td class="n">{{ $num(array_sum($revenue['payme'])) }} so'm</td></tr>
                        <tr><td>Bank o'tkazmasi (qo'lda)</td><td class="n">{{ $num(array_sum($revenue['manual'])) }} so'm</td></tr>
                        <tr><th>Jami ({{ $revenue['count'] }} ta to'lov)</th><th class="n">{{ $num($revenue['total']) }} so'm {{ $trend($revenue['trend']) }}</th></tr>
                    </tbody>
                </table>
            </section>
            <section>
                <h2>Tashkilotlar ({{ $organizations['total'] }})</h2>
                <table>
                    <thead><tr><th>Tashkilot</th><th class="n">Mualliflar</th></tr></thead>
                    <tbody>
                        @forelse ($organizations['top'] as $org)
                            <tr><td>{{ $org['name'] }}</td><td class="n">{{ $org['authors'] }}</td></tr>
                        @empty
                            <tr><td colspan="2" class="muted">Ma'lumot yo'q</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        </div>

        <h2>Eng faol mualliflar</h2>
        <table>
            <thead><tr><th>#</th><th>Muallif</th><th>Tashkilot</th><th class="n">Maqolalar</th></tr></thead>
            <tbody>
                @forelse ($topAuthors as $i => $author)
                    <tr><td>{{ $i + 1 }}</td><td>{{ $author['name'] }}</td><td>{{ $author['organization'] ?? '—' }}</td><td class="n">{{ $author['articles'] }}</td></tr>
                @empty
                    <tr><td colspan="4" class="muted">Ma'lumot yo'q</td></tr>
                @endforelse
            </tbody>
        </table>

        <h2>Taqrizchilar</h2>
        <table>
            <thead>
                <tr>
                    <th>Taqrizchi</th><th class="n">Takliflar</th><th class="n">Topshirgan</th><th class="n">Rad etgan</th>
                    <th class="n">Jarayonda</th><th class="n">Muddati o'tgan</th><th class="n">O'rt. kun</th><th class="n">Muddatida</th>
                </tr>
            </thead>
            <tbody>
                @forelse (array_filter($reviewers['rows'], fn ($r) => $r['invited'] + $r['completed'] + $r['pending'] > 0) as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td class="n">{{ $row['invited'] }}</td>
                        <td class="n">{{ $row['completed'] }}</td>
                        <td class="n">{{ $row['declined'] }}</td>
                        <td class="n">{{ $row['pending'] }}</td>
                        <td class="n">{{ $row['overdue'] }}</td>
                        <td class="n">{{ $num($row['avgDays']) }}</td>
                        <td class="n">{{ $row['onTime'] === null ? '—' : $row['onTime'].'%' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">Davrda taqrizlar yo'q</td></tr>
                @endforelse
            </tbody>
        </table>

        <footer>
            <span>{{ $journal['name'] ?? 'Inson va Jamiyat' }} — tahririyat statistikasi</span>
            <span>{{ config('app.url') }}</span>
        </footer>
    </main>
</body>
</html>
