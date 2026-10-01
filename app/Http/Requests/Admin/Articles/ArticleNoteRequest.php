<?php

namespace App\Http\Requests\Admin\Articles;

use App\Models\Article;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Muharrir izohi (tahririyat ichida, muallifga ko'rinmaydi).
 */
class ArticleNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $article = $this->route('article');

        return $article instanceof Article && Gate::allows('view', $article);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['body' => ['required', 'string', 'max:2000']];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['body' => __('Izoh')];
    }
}
