<?php

namespace App\Http\Requests\Cabinet\Articles;

use App\Enums\ArticleStatus;
use App\Models\Article;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Yangi maqola formasi so'rovlari uchun umumiy asos:
 * route'dagi maqola — faqat yuboruvchining qoralamasi (route'da maqola bo'lmasa — yangi qoralama).
 */
abstract class DraftArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $article = $this->route('article');

        if ($article === null) {
            return $this->user() !== null;
        }

        return $article instanceof Article
            && $article->status === ArticleStatus::Draft
            && Gate::allows('editDraft', $article);
    }

    protected function article(): ?Article
    {
        $article = $this->route('article');

        return $article instanceof Article ? $article : null;
    }
}
