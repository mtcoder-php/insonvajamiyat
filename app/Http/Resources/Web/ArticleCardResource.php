<?php

namespace App\Http\Resources\Web;

use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Maqola kartochkasi (bosh sahifa, katalog).
 * `authors` va `subject` oldindan yuklangan bo'lishi kerak (N+1 bo'lmasligi uchun).
 *
 * @mixin Article
 */
class ArticleCardResource extends JsonResource
{
    /** Kartochkada ko'rsatiladigan mualliflar soni */
    private const VISIBLE_AUTHORS = 2;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $authors = $this->authors;
        $names = $authors->take(self::VISIBLE_AUTHORS)
            ->map(fn (ArticleAuthor $author): string => $author->short_name)
            ->implode(', ');

        if ($authors->count() > self::VISIBLE_AUTHORS) {
            $names .= ' va boshq.';
        }

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'url' => route('articles.show', $this->slug),
            'title' => $this->title,
            'authors' => $names,
            'subject' => $this->subject ? [
                'name' => $this->subject->name,
                'slug' => $this->subject->slug,
            ] : null,
            'coverUrl' => MediaUrl::from($this->cover_image_path),
            'doi' => $this->doi,
            'publishedAt' => $this->published_at?->toDateString(),
            'views' => $this->views_count,
            'downloads' => $this->downloads_count,
        ];
    }
}
