<?php

namespace App\Http\Resources\Web;

use App\Models\Event;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Event
 */
class EventResource extends JsonResource
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
            'location' => $this->location,
            'startsAt' => $this->starts_at->toIso8601String(),
            'endsAt' => $this->ends_at?->toIso8601String(),
            'registrationUrl' => $this->registration_url,
            'imageUrl' => MediaUrl::from($this->image_path),
            'url' => route('events.show', $this->slug),
        ];
    }
}
