<?php

namespace App\Http\Requests\Admin\Production;

use App\Enums\PermissionName;
use App\Models\Article;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Nashr meta ma'lumotlari: DOI, UDK, plagiat foizi, jurnal soni va sahifalar.
 */
class ProductionMetadataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(PermissionName::ProductionManage->value);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $article = $this->route('article');

        return [
            'doi' => [
                'nullable', 'string', 'max:100', 'regex:/^10\.\d{4,9}\/\S+$/',
                Rule::unique('articles', 'doi')->ignore($article instanceof Article ? $article->id : null),
            ],
            'udc' => ['nullable', 'string', 'max:50'],
            'plagiarism_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'issue_id' => ['nullable', 'integer', Rule::exists('journal_issues', 'id')->whereNull('deleted_at')],
            'page_from' => ['nullable', 'required_with:page_to', 'integer', 'min:1', 'max:5000'],
            'page_to' => ['nullable', 'required_with:page_from', 'integer', 'gte:page_from', 'max:5000'],
        ];
    }

    /**
     * @return array{doi: string|null, udc: string|null, plagiarism_percent: float|null, issue_id: int|null, page_from: int|null, page_to: int|null}
     */
    public function metadata(): array
    {
        $issueId = $this->input('issue_id');

        return [
            'doi' => $this->filled('doi') ? $this->string('doi')->trim()->toString() : null,
            'udc' => $this->filled('udc') ? $this->string('udc')->trim()->toString() : null,
            'plagiarism_percent' => $this->filled('plagiarism_percent') ? $this->float('plagiarism_percent') : null,
            'issue_id' => is_numeric($issueId) ? (int) $issueId : null,
            // Son tanlanmagan bo'lsa sahifalar saqlanmaydi
            'page_from' => is_numeric($issueId) && $this->filled('page_from') ? $this->integer('page_from') : null,
            'page_to' => is_numeric($issueId) && $this->filled('page_to') ? $this->integer('page_to') : null,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'doi' => 'DOI',
            'udc' => __('UDK'),
            'plagiarism_percent' => __('Plagiat foizi'),
            'issue_id' => __('Jurnal soni'),
            'page_from' => __('Boshlang\'ich sahifa'),
            'page_to' => __('Oxirgi sahifa'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['doi.regex' => __('DOI formati: 10.xxxx/nom (masalan, 10.5281/insonvajamiyat.2026.0048)')];
    }
}
