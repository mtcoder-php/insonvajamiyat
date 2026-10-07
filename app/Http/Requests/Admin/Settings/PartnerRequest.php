<?php

namespace App\Http\Requests\Admin\Settings;

use App\Enums\PartnerType;
use App\Support\Translations;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(PartnerType::class)],
            ...Translations::rules('name', true, 150),
            ...Translations::rules('subtitle', false, 150),
            'url' => ['nullable', 'string', 'max:500', 'regex:/^https?:\/\//'],
            // SVG ataylab qabul qilinmaydi (ichida skript bo'lishi mumkin)
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=64,min_height=32'],
            'remove_logo' => ['sometimes', 'boolean'],
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
            'type' => 'turi',
            ...Translations::attributes('name', 'Nomi'),
            ...Translations::attributes('subtitle', 'Izoh'),
            'url' => 'sayt manzili',
            'logo' => 'logo',
            'sort_order' => 'tartib',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.regex' => 'Manzil http:// yoki https:// bilan boshlanishi kerak.',
        ];
    }
}
