<?php

namespace App\Services\Library;

use App\Enums\AuditEvent;
use App\Models\Book;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\ContentMedia;
use App\Support\Translations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Kutubxona: kitob qo'shish / tahrirlash / o'chirish, muqova (public disk, books/) va
 * PDF fayl (local disk, library/ — faqat route orqali o'qiladi), hisoblagichlar.
 */
class BookService
{
    /** PDF saqlanadigan disk (maxfiy) */
    public const DISK = 'local';

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data  self::data() natijasi
     */
    public function save(?Book $book, array $data, ?UploadedFile $cover, bool $removeCover, ?UploadedFile $pdf, bool $removePdf, User $user): Book
    {
        $book ??= new Book;
        $isNew = ! $book->exists;

        $book->replaceTranslations('title', $data['title']);
        $book->replaceTranslations('description', $data['description']);
        $book->forceFill([
            'subject_id' => $data['subject_id'],
            'author' => $data['author'],
            'publisher' => $data['publisher'],
            'year' => $data['year'],
            'pages' => $data['pages'],
            'isbn' => $data['isbn'],
            'language' => $data['language'],
            'url' => $data['url'],
            'is_featured' => $data['is_featured'],
            'is_active' => $data['is_active'],
            'sort_order' => $data['sort_order'],
        ]);
        $book->cover_image_path = ContentMedia::replace($book->cover_image_path, $cover, $removeCover, 'books', 'book');

        if ($pdf !== null) {
            $old = $book->file_path;
            $path = $pdf->storeAs(Book::FILE_DIR, Str::lower(Str::random(24)).'.pdf', self::DISK);

            if ($path === false) {
                throw new RuntimeException('Book file could not be stored.');
            }

            $book->forceFill([
                'file_path' => $path,
                'file_name' => mb_substr($pdf->getClientOriginalName() ?: 'kitob.pdf', 0, 200),
                'file_size' => $pdf->getSize() ?: null,
            ]);
            $this->deleteFile($old);
        } elseif ($removePdf) {
            $this->deleteFile($book->file_path);
            $book->forceFill(['file_path' => null, 'file_name' => null, 'file_size' => null]);
        }

        $book->save();

        $this->audit->log(AuditEvent::ContentSaved, $book, [
            'type' => 'book',
            'name' => $data['title']['uz'] ?? null,
            'created' => $isNew,
        ], actor: $user);

        return $book;
    }

    public function delete(Book $book, User $user): void
    {
        $cover = $book->cover_image_path;
        $file = $book->file_path;
        $name = $book->getTranslation('title', 'uz', false);
        $book->delete();
        ContentMedia::delete($cover);
        $this->deleteFile($file);

        $this->audit->log(AuditEvent::ContentDeleted, null, ['type' => 'book', 'name' => $name], actor: $user);
    }

    /** Ro'yxatdan tez almashtirish: tavsiya etilgan / faol */
    public function toggle(Book $book, string $field, bool $value): void
    {
        if (in_array($field, ['is_featured', 'is_active'], true)) {
            $book->forceFill([$field => $value])->save();
        }
    }

    /** Kitob sahifasi ochilganda (updated_at o'zgarmaydi) */
    public function recordView(Book $book): void
    {
        Book::query()->whereKey($book->id)->toBase()->increment('views_count');
    }

    /**
     * PDF: brauzerda o'qish (inline) yoki yuklab olish (attachment, hisoblanadi).
     */
    public function file(Book $book, bool $download): BinaryFileResponse|StreamedResponse
    {
        abort_unless($book->hasFile() && Storage::disk(self::DISK)->exists((string) $book->file_path), 404);

        $name = $this->fileName($book);

        if ($download) {
            Book::query()->whereKey($book->id)->toBase()->increment('downloads_count');

            return Storage::disk(self::DISK)->download((string) $book->file_path, $name, ['Content-Type' => 'application/pdf']);
        }

        return response()->file(Storage::disk(self::DISK)->path((string) $book->file_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.addslashes(Str::ascii($name)).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Formadan kelgan qiymatlar.
     *
     * @param  array<string, mixed>  $input
     * @return array{title: array<string, string>, description: array<string, string>, subject_id: int|null, author: string, publisher: string|null, year: int|null, pages: int|null, isbn: string|null, language: string, url: string|null, is_featured: bool, is_active: bool, sort_order: int}
     */
    public static function data(array $input): array
    {
        $text = fn (string $key): ?string => is_string($input[$key] ?? null) && trim($input[$key]) !== '' ? trim($input[$key]) : null;
        $number = fn (string $key): ?int => is_numeric($input[$key] ?? null) ? (int) $input[$key] : null;
        $language = $text('language');

        return [
            'title' => Translations::clean($input['title'] ?? []),
            'description' => Translations::clean($input['description'] ?? []),
            'subject_id' => $number('subject_id'),
            'author' => (string) $text('author'),
            'publisher' => $text('publisher'),
            'year' => $number('year'),
            'pages' => $number('pages'),
            'isbn' => $text('isbn'),
            'language' => in_array($language, ['uz', 'ru', 'en'], true) ? $language : 'uz',
            'url' => $text('url'),
            'is_featured' => (bool) ($input['is_featured'] ?? false),
            'is_active' => (bool) ($input['is_active'] ?? true),
            'sort_order' => $number('sort_order') ?? 0,
        ];
    }

    /** Yuklab olinadigan fayl nomi: "Kitob-nomi-Muallif.pdf" */
    private function fileName(Book $book): string
    {
        $title = (string) ($book->getTranslation('title', 'uz', false) ?: $book->title);
        $base = Str::slug(Str::ascii(Str::limit($title, 70, ''))) ?: 'kitob';

        return $base.'.pdf';
    }

    private function deleteFile(?string $path): void
    {
        if (is_string($path) && $path !== '') {
            Storage::disk(self::DISK)->delete($path);
        }
    }
}
