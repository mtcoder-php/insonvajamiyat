<?php

namespace App\Http\Requests\Cabinet\Articles;

use App\Enums\ArticleFileType;
use App\Models\Article;
use App\Services\Articles\RevisionService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;

/**
 * Tuzatilgan versiya: asosiy fayl (.docx/.pdf), taqrizchi va muharrirga javob, ilovalar.
 */
class ResubmitArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $article = $this->route('article');

        return $article instanceof Article && Gate::allows('update', $article);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $revision = ArticleFileType::Revision;
        $supplementary = ArticleFileType::Supplementary;

        return [
            'manuscript' => ['required', 'file', 'mimes:'.implode(',', $revision->allowedMimes()), 'max:'.$revision->maxSizeKb()],
            'response' => ['required', 'string', 'min:20', 'max:5000'],
            'supplementary' => ['nullable', 'array', 'max:'.RevisionService::MAX_SUPPLEMENTARY],
            'supplementary.*' => ['file', 'mimes:'.implode(',', $supplementary->allowedMimes()), 'max:'.$supplementary->maxSizeKb()],
        ];
    }

    /**
     * @return array<int, UploadedFile>
     */
    public function supplementary(): array
    {
        $files = $this->file('supplementary');

        return is_array($files) ? array_values($files) : [];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'manuscript' => __('Tuzatilgan fayl'),
            'response' => __('Taqrizchi va muharrirga javob'),
            'supplementary' => __('Ilovalar'),
            'supplementary.*' => __('Ilova'),
        ];
    }
}
