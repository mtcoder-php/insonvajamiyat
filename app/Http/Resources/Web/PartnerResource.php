<?php

namespace App\Http\Resources\Web;

use App\Models\Partner;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Partner
 */
class PartnerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'subtitle' => $this->subtitle,
            'logoUrl' => MediaUrl::from($this->logo_path),
            'url' => $this->url,
        ];
    }
}
