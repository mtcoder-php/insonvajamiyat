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
    private const WALL_TIME = 'Y-m-d\TH:i:s';

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
            // Tadbir vaqti — admin kiritgan "devor soati" (Toshkent): vaqt mintaqasisiz yuboriladi,
            // aks holda brauzer UTC deb qabul qilib +5 soat (va kechki tadbirda sanani) siljitadi
            'startsAt' => $this->starts_at->format(self::WALL_TIME),
            'endsAt' => $this->ends_at?->format(self::WALL_TIME),
            'registrationUrl' => $this->registration_url,
            'imageUrl' => MediaUrl::from($this->image_path),
            'url' => route('events.show', $this->slug),
        ];
    }
}
