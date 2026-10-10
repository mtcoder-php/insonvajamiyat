<?php

namespace App\Services\Content;

use App\Enums\AuditEvent;
use App\Models\Event;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\ContentMedia;
use App\Support\Html\RichText;
use App\Support\Translations;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;

/**
 * Tadbirlar: konferensiya, seminar, forum. Slug yaratilganda olinadi va o'zgarmaydi.
 */
class EventService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{title: array<string, string>, description: array<string, string>, location: array<string, string>, starts_at: CarbonImmutable, ends_at: CarbonImmutable|null, registration_url: string|null, is_published: bool}  $data
     */
    public function save(?Event $event, array $data, ?UploadedFile $image, bool $removeImage, User $user): Event
    {
        $event ??= new Event;
        $isNew = ! $event->exists;

        $event->replaceTranslations('title', $data['title']);
        $event->replaceTranslations('description', $data['description']);
        $event->replaceTranslations('location', $data['location']);
        $event->forceFill([
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'registration_url' => $data['registration_url'],
            'is_published' => $data['is_published'],
        ]);

        if ($isNew) {
            $event->slug = ContentMedia::uniqueSlug(Event::withTrashed(), $data['title']['uz'] ?? '', 'tadbir');
            $event->created_by = $user->id;
        }

        $event->image_path = ContentMedia::replace($event->image_path, $image, $removeImage, 'events', 'event');
        $event->save();

        $this->audit->log(AuditEvent::ContentSaved, $event, [
            'type' => 'event',
            'name' => $data['title']['uz'] ?? null,
            'created' => $isNew,
        ], actor: $user);

        return $event;
    }

    public function delete(Event $event, User $user): void
    {
        $name = $event->getTranslation('title', 'uz', false);
        $event->delete();

        $this->audit->log(AuditEvent::ContentDeleted, null, ['type' => 'event', 'name' => $name, 'id' => $event->id], actor: $user);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{title: array<string, string>, description: array<string, string>, location: array<string, string>, starts_at: CarbonImmutable, ends_at: CarbonImmutable|null, registration_url: string|null, is_published: bool}
     */
    public static function data(array $input): array
    {
        $starts = $input['starts_at'] ?? null;
        $ends = $input['ends_at'] ?? null;
        $url = $input['registration_url'] ?? null;

        return [
            'title' => Translations::clean($input['title'] ?? []),
            'description' => array_filter(array_map(RichText::toHtml(...), Translations::clean($input['description'] ?? []))),
            'location' => Translations::clean($input['location'] ?? []),
            'starts_at' => is_string($starts) && $starts !== '' ? CarbonImmutable::parse($starts) : CarbonImmutable::now(),
            'ends_at' => is_string($ends) && $ends !== '' ? CarbonImmutable::parse($ends) : null,
            'registration_url' => is_string($url) && trim($url) !== '' ? trim($url) : null,
            'is_published' => (bool) ($input['is_published'] ?? false),
        ];
    }
}
