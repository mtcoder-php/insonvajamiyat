<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\Web\BookResource;
use App\Models\Book;
use App\Models\Subject;
use App\Services\Library\BookService;
use App\Support\MediaUrl;
use App\Support\Seo\SeoMeta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Kutubxona (/library): kitoblar ro'yxati (qidiruv, yo'nalish, til, saralash), kitob sahifasi,
 * PDF ni brauzerda o'qish va yuklab olish (hisoblagich bilan).
 */
class LibraryController extends Controller
{
    public const PER_PAGE = 12;

    public const SORTS = ['recommended', 'newest', 'popular', 'title'];

    public function __construct(private readonly BookService $books) {}

    public function index(Request $request): Response
    {
        $q = trim($request->string('q')->toString());
        $subject = $request->string('subject')->toString() ?: null;
        $language = in_array($request->query('language'), ['uz', 'ru', 'en'], true) ? (string) $request->query('language') : null;
        $sort = in_array($request->query('sort'), self::SORTS, true) ? (string) $request->query('sort') : 'recommended';

        $query = Book::query()
            ->where('is_active', true)
            ->with('subject')
            ->when($q !== '', function (Builder $query) use ($q): void {
                $term = '%'.mb_strtolower($q).'%';
                $query->where(fn (Builder $w) => $w
                    ->whereRaw('lower(title) like ?', [$term])
                    ->orWhereRaw('lower(author) like ?', [$term])
                    ->orWhereRaw('lower(description) like ?', [$term]));
            })
            ->when($subject !== null, fn (Builder $query) => $query->whereHas('subject', fn (Builder $s) => $s->where('slug', $subject)))
            ->when($language !== null, fn (Builder $query) => $query->where('language', $language));

        match ($sort) {
            'newest' => $query->orderByDesc('year')->orderByDesc('id'),
            'popular' => $query->orderByDesc('downloads_count')->orderByDesc('views_count')->orderByDesc('id'),
            'title' => $query->orderBy('title->uz'),
            default => $query->orderByDesc('is_featured')->orderBy('sort_order')->orderByDesc('id'),
        };

        $books = $query->paginate(self::PER_PAGE)->withQueryString();

        // Faqat kitobi bor yo'nalishlar
        $subjects = Subject::query()
            ->where('is_active', true)
            ->whereIn('id', Book::query()->where('is_active', true)->whereNotNull('subject_id')->select('subject_id'))
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Subject $s): array => ['value' => $s->slug, 'label' => $s->name])
            ->all();

        return Inertia::render('web/library/Index', [
            'hero' => StaticPageController::hero('library'),
            'books' => BookResource::collection($books),
            'featured' => $q === '' && $subject === null && $language === null && $books->currentPage() === 1
                ? BookResource::collection(Book::query()->active()->featured()->with('subject')->limit(4)->get())->resolve()
                : [],
            'subjects' => $subjects,
            'filters' => ['q' => $q, 'subject' => $subject, 'language' => $language, 'sort' => $sort],
            'stats' => [
                'books' => Book::query()->where('is_active', true)->count(),
                'files' => Book::query()->where('is_active', true)->whereNotNull('file_path')->count(),
                'downloads' => (int) Book::query()->where('is_active', true)->sum('downloads_count'),
            ],
        ]);
    }

    public function show(Book $book): Response
    {
        abort_unless($book->is_active, 404);

        $this->books->recordView($book);
        $book->load('subject');
        app(SeoMeta::class)->forBook($book);

        // Avval shu yo'nalishdagi kitoblar, keyin qolganlari (tavsiya etilganlar oldinda)
        $related = Book::query()
            ->where('is_active', true)
            ->with('subject')
            ->whereKeyNot($book->id)
            ->when($book->subject_id !== null, fn (Builder $query) => $query->orderByRaw('case when subject_id = ? then 0 else 1 end', [$book->subject_id]))
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->limit(4)
            ->get();

        return Inertia::render('web/library/Show', [
            'hero' => StaticPageController::hero('library'),
            'book' => [
                ...BookResource::make($book)->resolve(),
                'description' => $book->description,
                'publisher' => $book->publisher,
                'isbn' => $book->isbn,
                'views' => $book->views_count + 1,
                'downloads' => $book->downloads_count,
                'file' => $book->hasFile() ? [
                    'size' => $book->file_size,
                    'readUrl' => route('library.read', $book->slug),
                    'downloadUrl' => route('library.download', $book->slug),
                ] : null,
                'externalUrl' => $book->url,
                'coverUrl' => MediaUrl::from($book->cover_image_path),
            ],
            'related' => BookResource::collection($related)->resolve(),
        ]);
    }

    public function read(Book $book): BinaryFileResponse|StreamedResponse
    {
        abort_unless($book->is_active, 404);

        return $this->books->file($book, false);
    }

    public function download(Book $book): BinaryFileResponse|StreamedResponse
    {
        abort_unless($book->is_active, 404);

        return $this->books->file($book, true);
    }
}
