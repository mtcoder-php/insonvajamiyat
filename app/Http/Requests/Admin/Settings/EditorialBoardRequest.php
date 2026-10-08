<?php

namespace App\Http\Requests\Admin\Settings;

use App\Enums\EditorialBoardRole;
use App\Support\Translations;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Tahririyat kengashi a'zosi.
 */
class EditorialBoardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // marshrut guruhidagi permission middleware
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'orcid' => $this->filled('orcid') ? strtoupper(trim($this->string('orcid')->toString())) : null,
            'country' => $this->filled('country') ? strtoupper(trim($this->string('country')->toString())) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', Rule::enum(EditorialBoardRole::class)],
            ...Translations::rules('full_name', true, 150),
            ...Translations::rules('position', false, 200),
            ...Translations::rules('organization', false, 250),
            ...Translations::rules('academic_degree', false, 150),
            'country' => ['nullable', 'string', 'size:2', 'alpha'],
            'email' => ['nullable', 'email', 'max:255'],
            'orcid' => ['nullable', 'string', 'regex:/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:min_width=120,min_height=120'],
            'remove_photo' => ['sometimes', 'boolean'],
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
            'role' => __('Lavozimi (kengashda)'),
            ...Translations::attributes('full_name', __('F.I.Sh.')),
            ...Translations::attributes('position', __('Lavozim')),
            ...Translations::attributes('organization', __('Tashkilot')),
            ...Translations::attributes('academic_degree', __('Ilmiy daraja')),
            'country' => __('Mamlakat'),
            'orcid' => 'ORCID',
            'photo' => __('Rasm'),
            'sort_order' => __('Tartib'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'orcid.regex' => __('ORCID formati: 0000-0000-0000-0000'),
            'country.size' => __('Mamlakat kodi — 2 harf (masalan, UZ)'),
        ];
    }
}
