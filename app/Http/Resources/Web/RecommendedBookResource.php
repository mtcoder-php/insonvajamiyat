<?php

namespace App\Http\Resources\Web;

use App\Models\RecommendedBook;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RecommendedBook
 */
class RecommendedBookResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'year' => $this->year,
            'coverUrl' => MediaUrl::from($this->cover_image_path),
            'url' => $this->url,
        ];
    }
}
