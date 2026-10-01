<?php

namespace App\Http\Requests\Articles;

use App\Models\Article;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Muallif ↔ tahririyat yozishmasiga xabar: matn va ixtiyoriy fayl (10 MB gacha).
 */
class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $article = $this->route('article');

        return $article instanceof Article && Gate::allows('message', $article);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:2', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,xlsx,png,jpg,jpeg,zip', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'body' => __('Xabar'),
            'attachment' => __('Fayl'),
        ];
    }
}
