<?php

namespace App\Http\Resources\Web;

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Subject
 */
class SubjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            // Klassik dizaynda nom ostida inglizcha (kursiv) yoziladi
            'nameEn' => $this->getTranslation('name', 'en', false) ?: null,
            'articlesCount' => $this->published_articles_count ?? 0,
        ];
    }
}
