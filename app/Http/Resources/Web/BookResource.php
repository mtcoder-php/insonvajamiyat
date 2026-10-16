<?php

namespace App\Http\Resources\Web;

use App\Models\Book;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Kutubxona kartochkasi (sayt: /library, bosh sahifa "Tavsiya etilgan kitoblar").
 *
 * @mixin Book
 */
class BookResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'author' => $this->author,
            'year' => $this->year,
            'pages' => $this->pages,
            'language' => $this->language,
            'coverUrl' => MediaUrl::from($this->cover_image_path),
            'subject' => $this->relationLoaded('subject') && $this->subject !== null
                ? ['name' => $this->subject->name, 'slug' => $this->subject->slug]
                : null,
            'hasFile' => $this->hasFile(),
            'isFeatured' => $this->is_featured,
            'pageUrl' => route('library.show', $this->slug),
            // Eski bosh sahifa kartasi uchun: PDF bo'lsa — kitob sahifasi, aks holda tashqi havola
            'url' => $this->url,
        ];
    }
}
