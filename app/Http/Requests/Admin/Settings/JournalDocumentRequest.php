<?php

namespace App\Http\Requests\Admin\Settings;

use App\Enums\JournalDocumentKind;
use App\Services\Content\JournalDocumentService;
use App\Support\Translations;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Yuklab olinadigan fayl (shablon, yo'riqnoma, shakl). Yangi yozuvda fayl majburiy,
 * tahrirlashda — ixtiyoriy (yuklanmasa eski fayl qoladi).
 */
class JournalDocumentRequest extends FormRequest
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
        $creating = $this->route('document') === null;

        return [
            'kind' => ['required', Rule::enum(JournalDocumentKind::class)],
            ...Translations::rules('title', true, 150),
            ...Translations::rules('description', false, 300),
            'file' => [
                $creating ? 'required' : 'nullable',
                'file',
                'mimes:'.implode(',', JournalDocumentService::EXTENSIONS),
                'extensions:'.implode(',', JournalDocumentService::EXTENSIONS),
                'max:'.JournalDocumentService::MAX_KB,
            ],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'kind' => __('Turi'),
            ...Translations::attributes('title', __('Nomi')),
            ...Translations::attributes('description', __('Izoh')),
            'file' => __('Fayl'),
            'sort_order' => __('Tartib'),
        ];
    }
}
