<?php

namespace App\Http\Resources\Web;

use App\Models\JournalIssue;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Jurnal soni kartochkasi. Ixtiyoriy hisoblangan maydonlar:
 * articles_count (withCount), pages_total (withMax) — yuklanmagan bo'lsa null.
 *
 * @mixin JournalIssue
 */
class IssueCardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $pagesTotal = $this->getAttribute('pages_total');

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'url' => route('issues.show', $this->slug),
            'label' => $this->label,
            'number' => $this->number,
            'year' => $this->year,
            'volume' => $this->volume,
            'title' => $this->title,
            'description' => $this->description,
            'coverUrl' => MediaUrl::from($this->cover_image_path),
            'pdfUrl' => MediaUrl::from($this->full_pdf_path),
            'pdfSize' => MediaUrl::size($this->full_pdf_path),
            'tocUrl' => MediaUrl::from($this->toc_file_path),
            'publishedAt' => $this->published_at?->toDateString(),
            'articlesCount' => $this->articles_count,
            'pagesTotal' => is_numeric($pagesTotal) ? (int) $pagesTotal : null,
        ];
    }
}
