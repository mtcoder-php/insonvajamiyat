<?php

namespace App\Services\Content;

use App\Enums\AuditEvent;
use App\Enums\JournalDocumentKind;
use App\Models\JournalDocument;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\MediaUrl;
use App\Support\Translations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Mualliflar uchun yuklab olinadigan fayllar (TZ 4.2.5): maqola shabloni, yo'riqnoma, shakllar.
 *
 * "Shablonni yuklab olish" tugmalari (yo'riqnoma, kabinet, maqola yuborish) birinchi faol
 * "Maqola shabloni" fayliga olib boradi; u bo'lmasa — eski config('journal.article_template')
 * (public/ ichidagi statik fayl) ishlaydi.
 */
class JournalDocumentService
{
    public const DIR = 'documents';

    public const MAX_KB = 20480;

    public const EXTENSIONS = ['doc', 'docx', 'dotx', 'pdf', 'rtf', 'odt', 'xls', 'xlsx', 'zip'];

    private ?string $template = null;

    private bool $templateResolved = false;

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Maqola shabloni havolasi (bir so'rov ichida bir marta hisoblanadi).
     */
    public function templateUrl(): ?string
    {
        if (! $this->templateResolved) {
            $document = JournalDocument::query()->active()
                ->where('kind', JournalDocumentKind::Template->value)
                ->orderBy('sort_order')->orderBy('id')
                ->first();

            $this->template = $document !== null
                ? route('documents.download', $document->id)
                : MediaUrl::publicAsset(config('journal.article_template'));
            $this->templateResolved = true;
        }

        return $this->template;
    }

    /**
     * Saytdagi ro'yxat ("Mualliflar uchun yo'riqnoma" → "Yuklab olinadigan fayllar").
     *
     * @return list<array{id: int, kind: string, kindLabel: string, title: string, description: string|null, extension: string, size: int, url: string, updatedAt: string|null}>
     */
    public function public(): array
    {
        return array_values(JournalDocument::query()->active()->ordered()->get()
            ->map(fn (JournalDocument $d): array => [
                'id' => $d->id,
                'kind' => $d->kind->value,
                'kindLabel' => $d->kind->label(),
                'title' => $d->title,
                'description' => $d->description !== '' ? $d->description : null,
                'extension' => $d->extension,
                'size' => $d->size,
                'url' => route('documents.download', $d->id),
                'updatedAt' => $d->updated_at?->toIso8601String(),
            ])
            ->all());
    }

    /**
     * Admin ro'yxati.
     *
     * @return list<array<string, mixed>>
     */
    public function admin(): array
    {
        return array_values(JournalDocument::query()->with('editor')->ordered()->get()
            ->map(fn (JournalDocument $d): array => [
                'id' => $d->id,
                'kind' => $d->kind->value,
                'kindLabel' => $d->kind->label(),
                'name' => $d->getTranslation('title', 'uz', false) ?: $d->title,
                'translations' => [
                    'title' => Translations::form($d->getTranslations('title')),
                    'description' => Translations::form($d->getTranslations('description')),
                ],
                'originalName' => $d->original_name,
                'extension' => $d->extension,
                'size' => $d->size,
                'downloads' => $d->downloads_count,
                'isActive' => $d->is_active,
                'sortOrder' => $d->sort_order,
                'updatedAt' => $d->updated_at?->toIso8601String(),
                'updatedBy' => $d->editor?->name,
                'urls' => [
                    'download' => route('documents.download', $d->id),
                    'update' => route('admin.settings.documents.update', $d->id),
                    'destroy' => route('admin.settings.documents.destroy', $d->id),
                ],
            ])
            ->all());
    }

    /**
     * @param  array{kind: JournalDocumentKind, title: array<string, string>, description: array<string, string>, is_active: bool, sort_order: int}  $data
     */
    public function save(?JournalDocument $document, array $data, ?UploadedFile $file, User $user): JournalDocument
    {
        $document ??= new JournalDocument;
        $isNew = ! $document->exists;

        if ($file === null && $isNew) {
            throw new RuntimeException('A file is required for a new document.');
        }

        $document->replaceTranslations('title', $data['title']);
        $document->replaceTranslations('description', $data['description']);
        $document->forceFill([
            'kind' => $data['kind'],
            'is_active' => $data['is_active'],
            'sort_order' => $data['sort_order'],
            'updated_by' => $user->id,
        ]);

        $old = null;

        if ($file !== null) {
            $old = $document->exists ? $document->path : null;
            $extension = strtolower($file->getClientOriginalExtension());
            $path = $file->storeAs(self::DIR, $data['kind']->value.'-'.Str::lower(Str::random(12)).'.'.$extension, MediaUrl::DISK);

            if ($path === false) {
                throw new RuntimeException('Document could not be stored.');
            }

            $document->forceFill([
                'path' => $path,
                'original_name' => self::fileName($file->getClientOriginalName(), $extension),
                'extension' => $extension,
                'size' => (int) $file->getSize(),
            ]);
        }

        $document->save();

        if ($old !== null && $old !== $document->path) {
            Storage::disk(MediaUrl::DISK)->delete($old);
        }

        $this->templateResolved = false;

        $this->audit->log(AuditEvent::ContentSaved, null, [
            'type' => 'journal_document',
            'name' => $data['title']['uz'] ?? null,
            'created' => $isNew,
            'file_replaced' => ! $isNew && $file !== null,
        ], actor: $user);

        return $document;
    }

    public function delete(JournalDocument $document, User $user): void
    {
        $path = $document->path;
        $name = $document->getTranslation('title', 'uz', false);
        $document->delete();
        Storage::disk(MediaUrl::DISK)->delete($path);
        $this->templateResolved = false;

        $this->audit->log(AuditEvent::ContentDeleted, null, ['type' => 'journal_document', 'name' => $name], actor: $user);
    }

    /**
     * Faylni asl nomi bilan beradi va yuklab olishlar sonini oshiradi.
     */
    public function download(JournalDocument $document): StreamedResponse
    {
        $disk = Storage::disk(MediaUrl::DISK);
        abort_unless($disk->exists($document->path), 404);

        JournalDocument::query()->whereKey($document->id)->increment('downloads_count');

        return $disk->download($document->path, $document->original_name);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{kind: JournalDocumentKind, title: array<string, string>, description: array<string, string>, is_active: bool, sort_order: int}
     */
    public static function data(array $input): array
    {
        $kind = $input['kind'] ?? null;
        $sort = $input['sort_order'] ?? 0;

        return [
            'kind' => JournalDocumentKind::tryFrom(is_string($kind) ? $kind : '') ?? JournalDocumentKind::Other,
            'title' => Translations::clean($input['title'] ?? []),
            'description' => Translations::clean($input['description'] ?? []),
            'is_active' => (bool) ($input['is_active'] ?? true),
            'sort_order' => is_numeric($sort) ? (int) $sort : 0,
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function kinds(): array
    {
        return array_map(fn (JournalDocumentKind $k): array => ['value' => $k->value, 'label' => $k->label()], JournalDocumentKind::cases());
    }

    /**
     * Yuklab olishdagi nom: yo'l belgilari va boshqaruv belgilarisiz, 150 belgigacha.
     */
    private static function fileName(string $name, string $extension): string
    {
        $base = pathinfo(str_replace(['/', '\\'], '-', $name), PATHINFO_FILENAME);
        $base = trim((string) preg_replace('/[\x00-\x1F\x7F"<>:|?*]+/u', '', $base), " .-\t");
        $base = Str::limit($base !== '' ? $base : 'document', 140, '');

        return $base.'.'.$extension;
    }
}
