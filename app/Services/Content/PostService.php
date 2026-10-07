<?php

namespace App\Services\Content;

use App\Enums\AuditEvent;
use App\Enums\PostType;
use App\Models\Post;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\ContentMedia;
use App\Support\Translations;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;

/**
 * Yangiliklar va e'lonlar. Slug yaratilganda o'zbekcha sarlavhadan olinadi va keyin
 * o'zgarmaydi (tashqi havolalar buzilmasligi uchun). Chop etilganda sana bo'sh bo'lsa — hozir;
 * kelajakdagi sana — rejalashtirilgan nashr.
 */
class PostService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{type: PostType, title: array<string, string>, excerpt: array<string, string>, body: array<string, string>, is_published: bool, is_pinned: bool, published_at: CarbonImmutable|null}  $data
     */
    public function save(?Post $post, array $data, ?UploadedFile $image, bool $removeImage, User $user): Post
    {
        $post ??= new Post;
        $isNew = ! $post->exists;

        $post->replaceTranslations('title', $data['title']);
        $post->replaceTranslations('excerpt', $data['excerpt']);
        $post->replaceTranslations('body', $data['body']);

        $publishedAt = $data['published_at'];

        if ($data['is_published'] && $publishedAt === null) {
            $publishedAt = $post->published_at !== null ? CarbonImmutable::instance($post->published_at) : CarbonImmutable::now();
        }

        $post->forceFill([
            'type' => $data['type'],
            'is_published' => $data['is_published'],
            'is_pinned' => $data['is_pinned'],
            'published_at' => $publishedAt,
        ]);

        if ($isNew) {
            $post->slug = ContentMedia::uniqueSlug(Post::withTrashed(), $data['title']['uz'] ?? '', 'xabar');
            $post->author_id = $user->id;
        }

        $post->image_path = ContentMedia::replace($post->image_path, $image, $removeImage, 'posts', 'post');
        $post->save();

        $this->audit->log(AuditEvent::ContentSaved, $post, [
            'type' => 'post',
            'name' => $data['title']['uz'] ?? null,
            'created' => $isNew,
        ], actor: $user);

        return $post;
    }

    /**
     * Yumshoq o'chirish: rasm saqlanib qoladi (tiklash mumkin).
     */
    public function delete(Post $post, User $user): void
    {
        $name = $post->getTranslation('title', 'uz', false);
        $post->delete();

        $this->audit->log(AuditEvent::ContentDeleted, null, ['type' => 'post', 'name' => $name, 'id' => $post->id], actor: $user);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{type: PostType, title: array<string, string>, excerpt: array<string, string>, body: array<string, string>, is_published: bool, is_pinned: bool, published_at: CarbonImmutable|null}
     */
    public static function data(array $input): array
    {
        $type = $input['type'] ?? null;
        $date = $input['published_at'] ?? null;

        return [
            'type' => PostType::tryFrom(is_string($type) ? $type : '') ?? PostType::News,
            'title' => Translations::clean($input['title'] ?? []),
            'excerpt' => Translations::clean($input['excerpt'] ?? []),
            'body' => Translations::clean($input['body'] ?? []),
            'is_published' => (bool) ($input['is_published'] ?? false),
            'is_pinned' => (bool) ($input['is_pinned'] ?? false),
            'published_at' => is_string($date) && $date !== '' ? CarbonImmutable::parse($date) : null,
        ];
    }
}
