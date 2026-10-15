<?php

namespace App\Services\Issues;

use App\Enums\ArticleFileType;
use App\Enums\AuditEvent;
use App\Enums\IssueStatus;
use App\Jobs\BuildIssuePdf;
use App\Models\ArticleFile;
use App\Models\IssueArticle;
use App\Models\JournalIssue;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\MediaUrl;
use App\Support\Pdf\ImagePdf;
use App\Support\Pdf\Qpdf;
use App\Support\Pdf\SimplePdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/**
 * To'liq son PDF ni avtomatik yig'ish:
 *   1-bet — muqova, 2-bet — jurnal ma'lumotlari, 3-betdan — maqolalarning yakuniy PDF lari
 *   (son tartibida, IssueService avtomatik hisoblagan betlarda), oxirida — mundarija.
 *
 * Maqola betlariga bet raqami va kolontitul (jurnal nomi, son) bosiladi — maketchi PDF ni
 * raqamsiz yuklaydi, tartib o'zgarsa ham raqamlar to'g'ri bo'ladi. Xatcho'plar (ruknlar →
 * maqolalar) qo'shiladi; PDF ko'ruvchidagi bet raqami bosilgan raqam bilan bir xil.
 * Ish navbatda bajariladi (BuildIssuePdf), holat journal_issues.pdf_status da.
 */
class IssuePdfBuilder
{
    public const QUEUED = 'queued';

    public const PROCESSING = 'processing';

    public const DONE = 'done';

    public const FAILED = 'failed';

    /** Maqolalardan oldingi betlar: muqova + jurnal ma'lumotlari */
    public const FRONT_PAGES = 2;

    /** Birinchi maqola boshlanadigan bet */
    public const FIRST_ARTICLE_PAGE = self::FRONT_PAGES + 1;

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly IssuePdfLayout $layout,
        private readonly IssueCoverService $covers,
    ) {}

    /**
     * Yig'ishdan oldingi tekshiruv. required=true bandlar bajarilmasa yig'ib bo'lmaydi.
     *
     * @return array<int, array{key: string, label: string, ok: bool, required: bool, detail: string|null}>
     */
    public function checks(JournalIssue $issue): array
    {
        $placements = $this->placements($issue);
        $missingPdf = $placements->filter(fn (IssueArticle $p): bool => $this->finalPdf($p) === null);
        $pagesProblem = $this->pagesProblem($placements);
        $qpdf = Qpdf::version();

        return [
            [
                'key' => 'qpdf',
                'label' => self::t('Serverda qpdf o\'rnatilgan'),
                'ok' => $qpdf !== null && Qpdf::available(),
                'required' => true,
                'detail' => $qpdf !== null ? "qpdf {$qpdf}" : 'sudo apt install qpdf (11.0+)',
            ],
            [
                'key' => 'articles',
                'label' => self::t('Songa maqolalar biriktirilgan'),
                'ok' => $placements->isNotEmpty(),
                'required' => true,
                'detail' => self::t(':count ta maqola', ['count' => $placements->count()]),
            ],
            [
                'key' => 'final_pdf',
                'label' => self::t('Barcha maqolalarning yakuniy PDF i bor'),
                'ok' => $placements->isNotEmpty() && $missingPdf->isEmpty(),
                'required' => true,
                'detail' => $missingPdf->isEmpty() ? null : self::t(":count ta maqolada yo'q", ['count' => $missingPdf->count()]),
            ],
            [
                'key' => 'pages',
                'label' => self::t('Betlar ketma-ket hisoblangan (:page-betdan)', ['page' => self::FIRST_ARTICLE_PAGE]),
                'ok' => $placements->isNotEmpty() && $pagesProblem === null,
                // Chop etilgan sonda betlar qulflangan — bosilgan raqam fayldagi o'rinni oladi
                'required' => $issue->status !== IssueStatus::Published,
                'detail' => $pagesProblem,
            ],
            [
                'key' => 'cover',
                'label' => self::t('1-bet: muqova'),
                'ok' => true,
                'required' => false,
                'detail' => match (true) {
                    $this->ownCover($issue) !== null => self::t("sonning o'z muqovasi"),
                    $this->covers->path() !== null => self::t('umumiy muqova (son raqami bilan)'),
                    default => self::t('avtomatik dizayn (son raqami bilan)'),
                },
            ],
            [
                'key' => 'info',
                'label' => self::t('2-bet: jurnal ma\'lumotlari'),
                'ok' => true,
                'required' => false,
                'detail' => $this->infoPath($issue) !== null ? self::t('yuklangan sahifa') : self::t('standart sahifa (avtomatik)'),
            ],
            [
                'key' => 'toc',
                'label' => self::t('Mundarija (oxirida)'),
                'ok' => true,
                'required' => false,
                'detail' => $this->tocPath($issue) !== null ? self::t('yuklangan sahifa') : self::t('avtomatik tuziladi'),
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

        /** @var array<int, array{file: string, title: string|null, section: string|null, kind: string, label: string|null}> $parts */
        $parts = [];

        // 1-bet: muqova — sonning o'zi → umumiy muqova + son raqami → avtomatik dizayn
        $file = $workdir.'/00-cover.pdf';
        file_put_contents($file, $this->coverPdf($issue));
        $parts[] = ['file' => $file, 'title' => 'Muqova', 'section' => null, 'kind' => 'front', 'label' => null];

        // 2-bet: jurnal ma'lumotlari — son uchun yuklangan sahifa (faqat 1-beti) yoki standart sahifa
        $file = $workdir.'/01-info.pdf';
        $info = $this->infoPath($issue);

        if ($info === null) {
            file_put_contents($file, $this->layout->infoPage($issue));
        } elseif (str_ends_with(strtolower($info), '.pdf')) {
            Qpdf::extract(Storage::disk(MediaUrl::DISK)->path($info), '1', $file);
        } else {
            file_put_contents($file, ImagePdf::fromImage(Storage::disk(MediaUrl::DISK)->get($info) ?? ''));
        }

        // Yuklangan sahifa o'z dizayni bilan qoladi — ramka/bant faqat standart sahifaga bosiladi
        $parts[] = ['file' => $file, 'title' => 'Jurnal haqida', 'section' => null, 'kind' => 'front', 'label' => $info === null ? IssuePdfLayout::INFO : null];

        // 3-betdan: maqolalar
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
                'kind' => 'article',
                'label' => IssuePdfLayout::ARTICLE,
            ];
            $articles++;
        }

        // Oxirida: mundarija — yuklangan bo'lsa o'sha, bo'lmasa maqolalar betlaridan avtomatik
        $toc = $this->tocPath($issue);
        $file = $workdir.'/99-toc.pdf';

        if ($toc !== null) {
            $contents = Storage::disk(MediaUrl::DISK)->get($toc) ?? '';
            file_put_contents($file, str_ends_with(strtolower($toc), '.pdf') ? $contents : ImagePdf::fromImage($contents));
        } else {
            file_put_contents($file, $this->layout->tocPages($issue, app(IssueWorkspace::class)->toc($issue)));
        }

        // Yuklangan mundarija o'z dizayni bilan qoladi
        $parts[] = ['file' => $file, 'title' => 'Mundarija', 'section' => null, 'kind' => 'toc', 'label' => $toc === null ? IssuePdfLayout::TOC : null];

        $merged = $workdir.'/merged.pdf';
        Qpdf::merge(array_column($parts, 'file'), $merged);

        // Har bir qismning birinchi beti (birlashtirilgan fayldagi o'rni) va har bir betga nima bosiladi
        $offset = 0;
        $labels = [];

        foreach ($parts as $i => $part) {
            $parts[$i]['index'] = $offset;
            $count = $this->pageCount($part['file']);

            for ($k = 0; $k < $count; $k++) {
                $labels[$offset + $k] = $part['label'];
            }

            $offset += $count;
        }

        // Ostiga krem fon; ustiga qo'sh ramka, naqshlar, kolontitul, shior va bet raqami
        $stamped = $workdir.'/stamped.pdf';
        $overlay = $workdir.'/overlay.pdf';
        $underlay = $workdir.'/underlay.pdf';
        $sizes = $this->pageSizes(Qpdf::inspect($merged));
        file_put_contents($underlay, $this->layout->underlay($sizes, $labels));
        file_put_contents($overlay, $this->layout->overlay($issue, $sizes, $labels));
        Qpdf::stamp($merged, $underlay, $overlay, $stamped);

        $final = $workdir.'/issue.pdf';
        $info = Qpdf::inspect($stamped);
        Qpdf::update($stamped, $this->navigation($info, $parts), $final);

        return ['file' => $final, 'pages' => $offset, 'articles' => $articles];
    }

    /**
     * Xatcho'plar (Outlines) va sahifa belgilari (PageLabels) — qpdf JSON v2 obyektlari.
     *
     * @param  array<string, mixed>  $info
     * @param  array<int, array{file: string, title: string|null, section: string|null, kind: string, index?: int}>  $parts
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

        // Bet belgilari: bosilgan raqam bilan bir xil (1, 2, 3 … — fayldagi tartib)
        $nums = [0, ['/S' => '/D']];

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
            ->with(['article.files', 'article.articleType'])
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

    /**
     * Betlar avtomatik hisoblangandek ketma-ketmi: birinchi maqola FIRST_ARTICLE_PAGE dan,
     * har biri yakuniy PDF hajmicha, oraliqsiz. Muammo bo'lsa — qisqa izoh.
     *
     * @param  Collection<int, IssueArticle>  $placements
     */
    private function pagesProblem(Collection $placements): ?string
    {
        $expected = self::FIRST_ARTICLE_PAGE;

        foreach ($placements as $placement) {
            $count = (int) ($placement->article->pages_count ?? 0);

            if ($placement->page_from === null || $count < 1) {
                return self::t("hajmi yoki beti noma'lum maqola bor");
            }

            if ($placement->page_from !== $expected || $placement->page_to !== $expected + $count - 1) {
                return self::t('«Betlarni hisoblash» tugmasini bosing');
            }

            $expected += $count;
        }

        return null;
    }

    /**
     * Har bir betning o'lchami (MediaBox, ota /Pages dan meros bo'lishi mumkin). Topilmasa — A4.
     *
     * @param  array<string, mixed>  $info
     * @return array<int, array{0: float, 1: float}>
     */
    private function pageSizes(array $info): array
    {
        $objects = is_array($info['qpdf'][1] ?? null) ? $info['qpdf'][1] : [];
        $pages = is_array($info['pages'] ?? null) ? $info['pages'] : [];
        $sizes = [];

        foreach (array_values($pages) as $page) {
            $ref = is_array($page) && is_string($page['object'] ?? null) ? $page['object'] : null;
            $box = null;

            for ($depth = 0; $ref !== null && $depth < 10 && $box === null; $depth++) {
                $value = $objects['obj:'.$ref]['value'] ?? null;

                if (! is_array($value)) {
                    break;
                }

                $candidate = $value['/MediaBox'] ?? null;

                if (is_array($candidate) && count($candidate) === 4 && array_filter($candidate, 'is_numeric') === $candidate) {
                    $box = array_map('floatval', $candidate);
                }

                $ref = is_string($value['/Parent'] ?? null) ? $value['/Parent'] : null;
            }

            $sizes[] = $box !== null
                ? [abs($box[2] - $box[0]), abs($box[3] - $box[1])]
                : [SimplePdf::A4_WIDTH, SimplePdf::A4_HEIGHT];
        }

        return $sizes;
    }

    /** 2-bet: son uchun yuklangan "jurnal ma'lumotlari" sahifasi (PDF yoki rasm) */
    private function infoPath(JournalIssue $issue): ?string
    {
        $path = $issue->info_file_path;

        if (! is_string($path) || $path === '' || ! Storage::disk(MediaUrl::DISK)->exists($path)) {
            return null;
        }

        return preg_match('/\.(pdf|jpe?g|png|webp)$/i', $path) === 1 ? $path : null;
    }

    /** Muqova rasmi: sonning o'zi yoki umumiy muqova (public/) */
    /**
     * Muqova sahifasi: son uchun yuklangan tayyor muqova → umumiy muqova (son raqami, yili yoziladi)
     * → umumiy muqova ham yo'q bo'lsa — to'liq avtomatik dizayn.
     */
    public function coverPdf(JournalIssue $issue): string
    {
        $own = $this->ownCover($issue);

        if ($own !== null) {
            return ImagePdf::fromImage($own);
        }

        $common = $this->covers->binary();

        if ($common !== null) {
            return $this->layout->templateCover($issue, $common, $this->covers->stamp(), $this->covers->position(), $this->covers->color());
        }

        return $this->layout->coverPage($issue, $this->coverArt());
    }

    /** Son uchun yuklangan tayyor muqova (rasm) */
    private function ownCover(JournalIssue $issue): ?string
    {
        $own = $issue->cover_image_path;

        if (is_string($own) && $own !== '' && ! str_starts_with($own, 'http') && Storage::disk(MediaUrl::DISK)->exists($own)) {
            return Storage::disk(MediaUrl::DISK)->get($own);
        }

        return null;
    }

    /** Avtomatik muqova uchun umumiy rasm (public/ ichida) */
    private function coverArt(): ?string
    {
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

    /**
     * @param  array<string, string|int>  $replace
     */
    private static function t(string $key, array $replace = []): string
    {
        $text = __($key, $replace);

        return is_string($text) ? $text : $key;
    }
}
