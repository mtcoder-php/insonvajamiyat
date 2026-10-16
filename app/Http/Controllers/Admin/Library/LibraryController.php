<?php

namespace App\Http\Controllers\Admin\Library;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Library\BookRequest;
use App\Models\Book;
use App\Models\Subject;
use App\Models\User;
use App\Services\Library\BookService;
use App\Support\MediaUrl;
use App\Support\Translations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin → Kutubxona: kitoblar ro'yxati (qidiruv, yo'nalish, holat), qo'shish/tahrirlash
 * (muqova, PDF, havola), "Tavsiya etilgan" va "Faol" belgilarini tez almashtirish.
 * Yangilash — POST (+ _method=put), fayl yuborish uchun.
 */
class LibraryController extends Controller
{
    public const PER_PAGE = 20;

    public const STATUSES = ['all', 'featured', 'active', 'hidden', 'pdf'];

    public function __construct(private readonly BookService $books) {}

    public function index(Request $request): Response
    {
        $q = trim($request->string('q')->toString());
        $status = in_array($request->query('status'), self::STATUSES, true) ? (string) $request->query('status') : 'all';
        $subject = $request->integer('subject') ?: null;

        $page = Book::query()
            ->with('subject')
            ->when($q !== '', function (Builder $query) use ($q): void {
                $term = '%'.mb_strtolower($q).'%';
                $query->where(fn (Builder $w) => $w
                    ->whereRaw('lower(title) like ?', [$term])
                    ->orWhereRaw('lower(author) like ?', [$term])
                    ->orWhere('isbn', 'like', '%'.$q.'%'));
            })
            ->when($subject !== null, fn (Builder $query) => $query->where('subject_id', $subject))
            ->when($status === 'featured', fn (Builder $query) => $query->where('is_featured', true))
            ->when($status === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when($status === 'hidden', fn (Builder $query) => $query->where('is_active', false))
            ->when($status === 'pdf', fn (Builder $query) => $query->whereNotNull('file_path'))
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('admin/library/Index', [
            'filters' => ['q' => $q, 'status' => $status, 'subject' => $subject],
            'stats' => [
                'total' => Book::query()->count(),
                'featured' => Book::query()->where('is_featured', true)->where('is_active', true)->count(),
                'files' => Book::query()->whereNotNull('file_path')->count(),
                'downloads' => (int) Book::query()->sum('downloads_count'),
                'views' => (int) Book::query()->sum('views_count'),
            ],
            'subjects' => Subject::query()->where('is_active', true)->orderBy('sort_order')->get()
                ->map(fn (Subject $s): array => ['value' => $s->id, 'label' => $s->name])->all(),
            'books' => [
                'data' => $page->getCollection()->map(fn (Book $book): array => $this->item($book))->all(),
                'meta' => [
                    'current_page' => $page->currentPage(),
                    'last_page' => $page->lastPage(),
                    'per_page' => $page->perPage(),
                    'total' => $page->total(),
                    'from' => $page->firstItem(),
                    'to' => $page->lastItem(),
                    'links' => $page->linkCollection()->all(),
                ],
            ],
            'storeUrl' => route('admin.library.store'),
        ]);
    }

    public function store(BookRequest $request): RedirectResponse
    {
        $this->books->save(null, BookService::data($request->validated()), $this->upload($request, 'cover'), false, $this->upload($request, 'file'), false, $this->user($request));

        return $this->done(__("Kitob qo'shildi."));
    }

    public function update(BookRequest $request, Book $book): RedirectResponse
    {
        $this->books->save(
            $book,
            BookService::data($request->validated()),
            $this->upload($request, 'cover'),
            $request->boolean('remove_cover'),
            $this->upload($request, 'file'),
            $request->boolean('remove_file'),
            $this->user($request),
        );

        return $this->done(__('Kitob saqlandi.'));
    }

    public function toggle(Request $request, Book $book): RedirectResponse
    {
        $data = $request->validate([
            'field' => ['required', Rule::in(['is_featured', 'is_active'])],
            'value' => ['required', 'boolean'],
        ]);

        $this->books->toggle($book, (string) $data['field'], (bool) $data['value']);

        return back();
    }

    public function destroy(Request $request, Book $book): RedirectResponse
    {
        $this->books->delete($book, $this->user($request));

        return $this->done(__("Kitob o'chirildi."));
    }

    /** Admin uchun PDF ko'rish (faol bo'lmagan kitob ham) */
    public function file(Book $book): BinaryFileResponse|StreamedResponse
    {
        return $this->books->file($book, false);
    }

    /**
     * @return array<string, mixed>
     */
    private function item(Book $book): array
    {
        /** @var array<string, string> $title */
        $title = $book->getTranslations('title');
        /** @var array<string, string> $description */
        $description = $book->getTranslations('description');

        return [
            'id' => $book->id,
            'slug' => $book->slug,
            'title' => $book->title,
            'author' => $book->author,
            'publisher' => $book->publisher,
            'year' => $book->year,
            'pages' => $book->pages,
            'isbn' => $book->isbn,
            'language' => $book->language,
            'subjectId' => $book->subject_id,
            'subject' => $book->subject?->name,
            'coverUrl' => MediaUrl::from($book->cover_image_path),
            'url' => $book->url,
            'file' => $book->hasFile() ? [
                'name' => $book->file_name,
                'size' => $book->file_size,
                'url' => route('admin.library.file', $book->slug),
            ] : null,
            'isFeatured' => $book->is_featured,
            'isActive' => $book->is_active,
            'sortOrder' => $book->sort_order,
            'views' => $book->views_count,
            'downloads' => $book->downloads_count,
            'translations' => [
                'title' => Translations::form($title),
                'description' => Translations::form($description),
            ],
            'urls' => [
                'update' => route('admin.library.update', $book->slug),
                'toggle' => route('admin.library.toggle', $book->slug),
                'destroy' => route('admin.library.destroy', $book->slug),
                'public' => $book->is_active ? route('library.show', $book->slug) : null,
            ],
        ];
    }

    private function upload(Request $request, string $key): ?UploadedFile
    {
        $file = $request->file($key);

        return $file instanceof UploadedFile ? $file : null;
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }

    private function done(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }
}
