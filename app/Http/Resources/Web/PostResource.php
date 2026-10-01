<?php

namespace App\Http\Resources\Web;

use App\Models\Post;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Yangilik / e'lon ro'yxat elementi.
 *
 * @mixin Post
 */
class PostResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'type' => $this->type->value,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'isPinned' => $this->is_pinned,
            'publishedAt' => $this->published_at?->toDateString(),
            'url' => route('news.show', $this->slug),
            'imageUrl' => MediaUrl::from($this->image_path),
        ];
    }
}
