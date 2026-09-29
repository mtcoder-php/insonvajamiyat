<?php

namespace App\Http\Resources\Web;

use App\Models\Banner;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Banner
 */
class BannerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'imageUrl' => MediaUrl::from($this->image_path),
            'linkUrl' => $this->link_url,
            'buttonText' => $this->button_text,
        ];
    }
}
