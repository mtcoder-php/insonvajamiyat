<?php

namespace App\Http\Requests\Admin\Settings;

use App\Services\Content\PageService;
use App\Support\Translations;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Statik sahifa: sarlavha, qisqa tavsif (SEO) va bo'limlar (sarlavha + matn, uch tilda).
 */
class PageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // marshrut guruhidagi permission middleware
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...Translations::rules('title', true, 150),
            ...Translations::rules('description', false, 300),
            'sections' => ['present', 'array', 'max:'.PageService::MAX_SECTIONS],
            'sections.*' => ['array'],
            'sections.*.heading' => ['nullable', 'array'],
            'sections.*.heading.uz' => ['nullable', 'string', 'max:200'],
            'sections.*.heading.ru' => ['nullable', 'string', 'max:200'],
            'sections.*.heading.en' => ['nullable', 'string', 'max:200'],
            'sections.*.body' => ['nullable', 'array'],
            'sections.*.body.uz' => ['nullable', 'string', 'max:10000'],
            'sections.*.body.ru' => ['nullable', 'string', 'max:10000'],
            'sections.*.body.en' => ['nullable', 'string', 'max:10000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            ...Translations::attributes('title', __('Sarlavha')),
            ...Translations::attributes('description', __('Qisqa tavsif')),
            'sections' => __("Bo'limlar"),
        ];
    }
}
