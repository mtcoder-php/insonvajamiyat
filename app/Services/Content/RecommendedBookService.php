<?php

namespace App\Services\Content;

use App\Enums\AuditEvent;
use App\Models\RecommendedBook;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\ContentMedia;
use App\Support\Translations;
use Illuminate\Http\UploadedFile;

/**
 * Tavsiya etilgan kitoblar (bosh sahifa o'ng ustuni). Muqova public diskda books/ papkasida.
 */
class RecommendedBookService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{title: array<string, string>, author: string, year: int|null, url: string|null, is_active: bool, sort_order: int}  $data
     */
    public function save(?RecommendedBook $book, array $data, ?UploadedFile $cover, bool $removeCover, User $user): RecommendedBook
    {
        $book ??= new RecommendedBook;
        $isNew = ! $book->exists;

        $book->replaceTranslations('title', $data['title']);
        $book->forceFill([
            'author' => $data['author'],
            'year' => $data['year'],
            'url' => $data['url'],
            'is_active' => $data['is_active'],
            'sort_order' => $data['sort_order'],
        ]);
        $book->cover_image_path = ContentMedia::replace($book->cover_image_path, $cover, $removeCover, 'books', 'book');
        $book->save();

        $this->audit->log(AuditEvent::ContentSaved, $book, [
            'type' => 'book',
            'name' => $data['title']['uz'] ?? null,
            'created' => $isNew,
        ], actor: $user);

        return $book;
    }

    public function delete(RecommendedBook $book, User $user): void
    {
        $path = $book->cover_image_path;
        $name = $book->getTranslation('title', 'uz', false);
        $book->delete();
        ContentMedia::delete($path);

        $this->audit->log(AuditEvent::ContentDeleted, null, ['type' => 'book', 'name' => $name], actor: $user);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{title: array<string, string>, author: string, year: int|null, url: string|null, is_active: bool, sort_order: int}
     */
    public static function data(array $input): array
    {
        $author = $input['author'] ?? '';
        $year = $input['year'] ?? null;
        $url = $input['url'] ?? null;
        $sort = $input['sort_order'] ?? 0;

        return [
            'title' => Translations::clean($input['title'] ?? []),
            'author' => is_string($author) ? trim($author) : '',
            'year' => is_numeric($year) ? (int) $year : null,
            'url' => is_string($url) && trim($url) !== '' ? trim($url) : null,
            'is_active' => (bool) ($input['is_active'] ?? true),
            'sort_order' => is_numeric($sort) ? (int) $sort : 0,
        ];
    }
}
