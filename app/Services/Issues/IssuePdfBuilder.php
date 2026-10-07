<?php

namespace App\Services\Issues;

use App\Enums\ArticleFileType;
use App\Enums\AuditEvent;
use App\Jobs\BuildIssuePdf;
use App\Models\ArticleFile;
use App\Models\IssueArticle;
use App\Models\JournalIssue;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\MediaUrl;
use App\Support\Pdf\ImagePdf;
use App\Support\Pdf\Qpdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/**
 * To'liq son PDF ni avtomatik yig'ish: muqova → mundarija → maqolalarning yakuniy PDF lari (son tartibida).
 *
 * Natijada: xatcho'plar (Muqova, Mundarija, ruknlar → maqolalar) va sahifa belgilari
 * (muqova/mundarija — i, ii…; maqolalar — jurnaldagi sahifa raqamidan) qo'shiladi,
 * shuning uchun PDF ko'ruvchida sahifa raqamlari jurnaldagidek ko'rinadi.
 * Ish navbatda bajariladi (BuildIssuePdf), holat journal_issues.pdf_status da.
 */
class IssuePdfBuilder
{
    public const QUEUED = 'queued';

    public const PROCESSING = 'processing';

    public const DONE = 'done';

    public const FAILED = 'failed';

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Yig'ishdan oldingi tekshiruv. required=true bandlar bajarilmasa yig'ib bo'lmaydi.
     *
     * @return array<int, array{key: string, label: string, ok: bool, required: bool, detail: string|null}>
     */
    public function checks(JournalIssue $issue): array
    {
        $placements = $this->placements($issue);
        $missingPdf = $placements->filter(fn (IssueArticle $p): bool => $this->finalPdf($p) === null);
        $missingPages = $placements->filter(fn (IssueArticle $p): bool => $p->page_from === null);
        $qpdf = Qpdf::version();

        return [
            [
                'key' => 'qpdf',
                'label' => 'Serverda qpdf o\'rnatilgan',
                'ok' => $qpdf !== null && Qpdf::available(),
                'required' => true,
                'detail' => $qpdf !== null ? "qpdf {$qpdf}" : 'sudo apt install qpdf (11.0+)',
            ],
            [
                'key' => 'articles',
                'label' => 'Songa maqolalar biriktirilgan',
                'ok' => $placements->isNotEmpty(),
                'required' => true,
                'detail' => $placements->count().' ta maqola',
            ],
            [
                'key' => 'final_pdf',
                'label' => 'Barcha maqolalarning yakuniy PDF i bor',
                'ok' => $placements->isNotEmpty() && $missingPdf->isEmpty(),
                'required' => true,
                'detail' => $missingPdf->isEmpty() ? null : $missingPdf->count().' ta maqolada yo\'q',
            ],
            [
                'key' => 'pages',
                'label' => 'Sahifa raqamlari belgilangan',
                'ok' => $placements->isNotEmpty() && $missingPages->isEmpty(),
                'required' => false,
                'detail' => $missingPages->isEmpty() ? null : 'xatcho\'plarda sahifa raqami ketma-ket beriladi',
            ],
            [
                'key' => 'cover',
                'label' => 'Muqova',
                'ok' => $this->coverBinary($issue) !== null,
                'required' => false,
                'detail' => filled($issue->cover_image_path) ? 'sonning o\'z muqovasi' : 'umumiy muqova ishlatiladi',
            ],
            [
                'key' => 'toc',
                'label' => 'Mundarija fayli yuklangan',
                'ok' => $this->tocPath($issue) !== null,
                'required' => false,
                'detail' => $this->tocPath($issue) !== null ? null : 'mundarijasiz yig\'iladi',
            ],
        ];
    }

    public function canBuild(JournalIssue $issue): bool
    {
        return collect($this->checks($issue))->every(fn (array $c): bool => $c['ok'] || ! $c['required']);
    }

    public function isBusy(JournalIssue $issue): bool
    {
        return in_array($issue->pdf_status, [self::QUEUED, self::PROCESSING], true);
    }

    /**
     * Navbatga qo'yish (admin tugmasi).
     *
     * @throws ValidationException
     */
    public function queue(JournalIssue $issue, User $user): void
    {
        if ($this->isBusy($issue)) {
            throw ValidationException::withMessages(['pdf' => __("Son PDF i allaqachon yig'ilmoqda.")]);
        }

        if (! $this->canBuild($issue)) {
            throw ValidationException::withMessages(['pdf' => __("Son PDF ini yig'ib bo'lmaydi: tekshiruv bandlarini bajaring.")]);
        }

        $issue->forceFill(['pdf_status' => self::QUEUED, 'pdf_error' => null])->save();

        BuildIssuePdf::dispatch($issue->id, $user->id);
    }

    /**
     * Yig'ish (job ichidan).
     */
    public function build(JournalIssue $issue, ?User $user = null): void
    {
        $issue->forceFill(['pdf_status' => self::PROCESSING, 'pdf_error' => null])->save();
        $workdir = storage_path('app/private/tmp/issue-pdf-'.Str::lower(Str::random(10)));
        File::ensureDirectoryExists($workdir);

        try {
            $result = $this->assemble($issue, $workdir);

            $old = $issue->full_pdf_path;
            $path = 'issues/'.$issue->slug.'/inson-va-jamiyat-'.$issue->slug.'-'.Str::lower(Str::random(6)).'.pdf';
            $stream = fopen($result['file'], 'rb');

            if ($stream === false || ! Storage::disk(MediaUrl::DISK)->put($path, $stream)) {
                throw new RuntimeException('Issue PDF could not be stored.');
            }

            if (is_resource($stream)) {
                fclose($stream);
            }

            if (is_string($old) && $old !== '' && $old !== $path && ! str_starts_with($old, 'http')) {
                Storage::disk(MediaUrl::DISK)->delete($old);
            }

            $issue->forceFill([
                'full_pdf_path' => $path,
                'pdf_status' => self::DONE,
                'pdf_error' => null,
                'pdf_pages' => $result['pages'],
                'pdf_auto' => true,
                'pdf_built_at' => now(),
            ])->save();

            $this->audit->log(AuditEvent::IssuePdfBuilt, $issue, [
                'pages' => $result['pages'],
                'articles' => $result['articles'],
            ], actor: $user);
        } catch (Throwable $e) {
            report($e);
            $this->fail($issue, $e->getMessage());
        } finally {
            File::deleteDirectory($workdir);
        }
    }

    public function fail(JournalIssue $issue, string $message): void
    {
        $issue->forceFill([
            'pdf_status' => self::FAILED,
            'pdf_error' => mb_substr($message, 0, 1000),
        ])->save();
    }

    /**
     * @return array{file: string, pages: int, articles: int}
     */
    private function assemble(JournalIssue $issue, string $workdir): array
    {
        if (! Qpdf::available()) {
            throw new RuntimeException("Serverda qpdf (11+) topilmadi. O'rnating: sudo apt install qpdf");
        }

        /** @var array<int, array{file: string, title: string|null, section: string|null, pageFrom: int|null, front: bool}> $parts */
        $parts = [];

        $cover = $this->coverBinary($issue);

        if ($cover !== null) {
            $file = $workdir.'/00-cover.pdf';
            file_put_contents($file, ImagePdf::fromImage($cover));
            $parts[] = ['file' => $file, 'title' => 'Muqova', 'section' => null, 'pageFrom' => null, 'front' => true];
        }

        $toc = $this->tocPath($issue);

        if ($toc !== null) {
            $file = $workdir.'/01-toc.pdf';
            $contents = Storage::disk(MediaUrl::DISK)->get($toc) ?? '';
            file_put_contents($file, str_ends_with(strtolower($toc), '.pdf') ? $contents : ImagePdf::fromImage($contents));
            $parts[] = ['file' => $file, 'title' => 'Mundarija', 'section' => null, 'pageFrom' => null, 'front' => true];
        }

        $articles = 0;

        foreach ($this->placements($issue) as $index => $placement) {
            $final = $this->finalPdf($placement) ?? throw new RuntimeException(
                'Yakuniy PDF topilmadi: '.$placement->article->title,
            );

            $source = Storage::disk($final->disk)->path($final->path);

            if (! is_file($source)) {
                throw new RuntimeException('Yakuniy PDF fayli diskda yo\'q: '.$placement->article->title);
            }

            $file = sprintf('%s/%03d-article.pdf', $workdir, $index + 10);
            copy($source, $file);

            $parts[] = [
                'file' => $file,
                'title' => $placement->article->title,
                'section' => IssueWorkspace::section($placement),
                'pageFrom' => $placement->page_from,
                'front' => false,
            ];
            $articles++;
        }

        $merged = $workdir.'/merged.pdf';
        Qpdf::merge(array_column($parts, 'file'), $merged);

        // Har bir qismning birinchi sahifasi (birlashtirilgan fayldagi o'rni)
        $offset = 0;

        foreach ($parts as $i => $part) {
            $parts[$i]['index'] = $offset;
            $offset += $this->pageCount($part['file']);
        }

        $final = $workdir.'/issue.pdf';
        $info = Qpdf::inspect($merged);
        Qpdf::update($merged, $this->navigation($info, $parts), $final);

        return ['file' => $final, 'pages' => $offset, 'articles' => $articles];
    }

    /**
     * Xatcho'plar (Outlines) va sahifa belgilari (PageLabels) — qpdf JSON v2 obyektlari.
     *
     * @param  array<string, mixed>  $info
     * @param  array<int, array{file: string, title: string|null, section: string|null, pageFrom: int|null, front: bool, index?: int}>  $parts
     * @return array<string, mixed>
     */
    private function navigation(array $info, array $parts): array
    {
        $pages = array_values(array_map(
            fn (mixed $p): string => is_array($p) && is_string($p['object'] ?? null) ? $p['object'] : '',
            is_array($info['pages'] ?? null) ? $info['pages'] : [],
        ));
        $meta = is_array($info['qpdf'][0] ?? null) ? $info['qpdf'][0] : [];
        $objects = is_array($info['qpdf'][1] ?? null) ? $info['qpdf'][1] : [];
        $root = is_string($objects['trailer']['value']['/Root'] ?? null) ? $objects['trailer']['value']['/Root'] : null;
        $catalog = $root !== null && is_array($objects['obj:'.$root]['value'] ?? null) ? $objects['obj:'.$root]['value'] : null;

        if ($root === null || $catalog === null || $pages === []) {
            throw new RuntimeException('PDF tuzilmasini o\'qib bo\'lmadi (qpdf JSON).');
        }

        $next = (is_numeric($meta['maxobjectid'] ?? null) ? (int) $meta['maxobjectid'] : 0) + 1;
        $ref = function () use (&$next): string {
            return ($next++).' 0 R';
        };

        // Daraxt: oldingi qismlar (Muqova, Mundarija) va ruknlar → maqolalar
        $tree = [];

        foreach ($parts as $part) {
            $node = ['title' => (string) $part['title'], 'page' => $pages[$part['index'] ?? 0] ?? $pages[0], 'kids' => []];

            if ($part['section'] !== null) {
                $last = array_key_last($tree);

                if ($last === null || ($tree[$last]['section'] ?? null) !== $part['section']) {
                    $tree[] = ['title' => $part['section'], 'page' => $node['page'], 'kids' => [], 'section' => $part['section']];
                    $last = array_key_last($tree);
                }

                $tree[$last]['kids'][] = $node;
            } else {
                $tree[] = $node;
            }
        }

        $updates = [];
        $outlines = $ref();
        [$first, $last, $count] = $this->outlineLevel($tree, $outlines, $ref, $updates);

        $updates['obj:'.$outlines] = ['value' => [
            '/Type' => '/Outlines',
            '/First' => $first,
            '/Last' => $last,
            '/Count' => $count,
        ]];

        // Sahifa belgilari: oldingi qismlar — rim raqamlari, maqolalar — jurnal sahifasidan
        $nums = [];
        $front = false;

        foreach ($parts as $part) {
            if ($part['front']) {
                if (! $front) {
                    $nums[] = $part['index'] ?? 0;
                    $nums[] = ['/S' => '/r'];
                    $front = true;
                }

                continue;
            }

            $nums[] = $part['index'] ?? 0;
            $nums[] = $part['pageFrom'] !== null ? ['/S' => '/D', '/St' => $part['pageFrom']] : ['/S' => '/D'];
        }

        $catalog['/Outlines'] = $outlines;
        $catalog['/PageMode'] = '/UseOutlines';
        $catalog['/PageLabels'] = ['/Nums' => $nums];
        $updates['obj:'.$root] = ['value' => $catalog];

        return $updates;
    }

    /**
     * @param  array<int, array{title: string, page: string, kids: array<int, mixed>}>  $nodes
     * @param  array<string, mixed>  $updates
     * @return array{0: string, 1: string, 2: int}
     */
    private function outlineLevel(array $nodes, string $parent, \Closure $ref, array &$updates): array
    {
        $ids = array_map(fn (): string => $ref(), $nodes);
        $size = count($nodes);
        $total = $size;

        foreach (array_values($nodes) as $i => $node) {
            $value = [
                '/Title' => 'u:'.mb_substr($node['title'], 0, 250),
                '/Parent' => $parent,
                '/Dest' => [$node['page'], '/Fit'],
            ];

            if ($i > 0) {
                $value['/Prev'] = $ids[$i - 1];
            }

            if ($i < $size - 1) {
                $value['/Next'] = $ids[$i + 1];
            }

            /** @var array<int, array{title: string, page: string, kids: array<int, mixed>}> $kids */
            $kids = $node['kids'];

            if ($kids !== []) {
                [$first, $last, $count] = $this->outlineLevel($kids, $ids[$i], $ref, $updates);
                $value['/First'] = $first;
                $value['/Last'] = $last;
                $value['/Count'] = $count;
                $total += $count;
            }

            $updates['obj:'.$ids[$i]] = ['value' => $value];
        }

        return [$ids[0], $ids[count($ids) - 1], $total];
    }

    private function pageCount(string $file): int
    {
        $info = Qpdf::inspect($file);

        return is_array($info['pages'] ?? null) ? count($info['pages']) : 0;
    }

    /**
     * @return Collection<int, IssueArticle>
     */
    private function placements(JournalIssue $issue): Collection
    {
        return IssueArticle::query()
            ->where('journal_issue_id', $issue->id)
            ->with(['article.files'])
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->values();
    }

    private function finalPdf(IssueArticle $placement): ?ArticleFile
    {
        return $placement->article->files
            ->filter(fn (ArticleFile $f): bool => $f->type === ArticleFileType::FinalPdf)
            ->sortByDesc('id')
            ->first();
    }

    /** Muqova rasmi: sonning o'zi yoki umumiy muqova (public/) */
    private function coverBinary(JournalIssue $issue): ?string
    {
        $own = $issue->cover_image_path;

        if (is_string($own) && $own !== '' && ! str_starts_with($own, 'http') && Storage::disk(MediaUrl::DISK)->exists($own)) {
            return Storage::disk(MediaUrl::DISK)->get($own);
        }

        $default = config('journal.default_issue_cover');
        $path = is_string($default) && $default !== '' ? public_path($default) : null;

        return $path !== null && is_file($path) ? (string) file_get_contents($path) : null;
    }

    private function tocPath(JournalIssue $issue): ?string
    {
        $toc = $issue->toc_file_path;

        if (! is_string($toc) || $toc === '' || str_starts_with($toc, 'http') || ! Storage::disk(MediaUrl::DISK)->exists($toc)) {
            return null;
        }

        return preg_match('/\.(pdf|jpe?g|png|webp)$/i', $toc) === 1 ? $toc : null;
    }
}
