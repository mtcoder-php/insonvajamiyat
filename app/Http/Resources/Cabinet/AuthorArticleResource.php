<?php

namespace App\Http\Resources\Cabinet;

use App\Enums\ArticleStatus;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Muallif kabineti — "Mening maqolalarim" qatori.
 *
 * @mixin Article
 */
class AuthorArticleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $issue = $this->relationLoaded('issues') ? $this->issues->first() : null;
        $keywords = $this->getTranslation('keywords', app()->getLocale(), true);

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'title' => $this->title,
            'subject' => $this->relationLoaded('subject') ? $this->subject?->name : null,
            'keywords' => is_array($keywords) ? array_slice(array_values(array_filter($keywords, 'is_string')), 0, 3) : [],
            'issue' => $issue?->label,
            'status' => $this->status->value,
            'statusGroup' => $this->status->group(),
            'statusLabel' => $this->status->label(),
            'submittedAt' => $this->submitted_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
            'url' => route('cabinet.articles.show', $this->uuid),
            'publicUrl' => $this->isPublished() ? route('articles.show', $this->slug) : null,
            // Qoralama — formani davom ettirish havolasi (faqat yuboruvchiga)
            'editUrl' => $this->status === ArticleStatus::Draft && $this->submitter_id === $request->user()?->id
                ? route('cabinet.articles.edit', $this->uuid)
                : null,
        ];
    }
}
