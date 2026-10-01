<?php

namespace App\Http\Requests\Admin\Articles;

use App\Models\Article;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Maqolaga mas'ul muharrirni biriktirish (bo'sh — biriktiruvni olib tashlash).
 */
class AssignEditorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $article = $this->route('article');

        return $article instanceof Article && Gate::allows('decide', $article);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['editor_id' => ['nullable', 'integer', 'exists:users,id']];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['editor_id' => __("Mas'ul muharrir")];
    }
}
